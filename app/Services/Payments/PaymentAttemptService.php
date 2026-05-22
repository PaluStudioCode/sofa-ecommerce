<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Services\Midtrans\MidtransPaymentGateway;
use App\Services\Notifications\WhatsAppNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentAttemptService
{
    public function __construct(
        private readonly MidtransPaymentGateway $gateway,
        private readonly WhatsAppNotificationService $notifications,
    ) {
    }

    public function createAttempt(Order $order): Payment
    {
        return DB::transaction(function () use ($order) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->with(['items.variant', 'payments'])
                ->lockForUpdate()
                ->findOrFail($order->id);

            $this->assertCanCreateAttempt($lockedOrder);

            $attemptNumber = ((int) $lockedOrder->payments()->max('attempt_number')) + 1;

            if ($attemptNumber > 1) {
                $this->reserveStockForOrder($lockedOrder);
            }

            $midtransOrderId = $lockedOrder->order_number.'-ATTEMPT-'.$attemptNumber;
            $snap = $this->gateway->createSnapTransaction($this->snapPayload($lockedOrder, $midtransOrderId));

            $payment = $lockedOrder->payments()->create([
                'attempt_number' => $attemptNumber,
                'midtrans_order_id' => $midtransOrderId,
                'status' => 'pending',
                'transaction_status' => 'pending',
                'gross_amount' => $lockedOrder->total_amount,
                'snap_token' => $snap['token'] ?? null,
                'redirect_url' => $snap['redirect_url'] ?? null,
                'expired_at' => now()->addDay(),
                'raw_response' => $snap,
            ]);

            $lockedOrder->update([
                'payment_status' => 'pending',
                'order_status' => 'menunggu_pembayaran',
            ]);

            return $payment;
        });
    }

    public function handleCallback(array $payload): Payment
    {
        if (! $this->gateway->verifyCallbackSignature($payload)) {
            throw ValidationException::withMessages([
                'signature_key' => 'Signature Midtrans tidak valid.',
            ]);
        }

        $midtransOrderId = (string) ($payload['order_id'] ?? '');

        /** @var Payment|null $payment */
        $payment = Payment::query()
            ->where('midtrans_order_id', $midtransOrderId)
            ->first();

        if (! $payment) {
            throw ValidationException::withMessages([
                'order_id' => 'Payment attempt tidak ditemukan.',
            ]);
        }

        $grossAmount = (float) ($payload['gross_amount'] ?? -1);

        if (abs(((float) $payment->gross_amount) - $grossAmount) >= 0.01) {
            throw ValidationException::withMessages([
                'gross_amount' => 'Gross amount Midtrans tidak sesuai dengan total pesanan.',
            ]);
        }

        return DB::transaction(function () use ($payment, $payload) {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()
                ->with(['order.items.variant', 'order.payments'])
                ->lockForUpdate()
                ->findOrFail($payment->id);

            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($lockedPayment->order_id);

            $previousStatus = $lockedPayment->status;
            $mappedStatus = $this->gateway->mapTransactionStatus(
                $payload['transaction_status'] ?? null,
                $payload['fraud_status'] ?? null
            );

            $successPaymentExists = $order->payments()
                ->where('status', 'success')
                ->whereKeyNot($lockedPayment->id)
                ->exists();

            $update = [
                'midtrans_transaction_id' => $payload['transaction_id'] ?? $lockedPayment->midtrans_transaction_id,
                'payment_type' => $payload['payment_type'] ?? $lockedPayment->payment_type,
                'transaction_status' => $payload['transaction_status'] ?? $lockedPayment->transaction_status,
                'fraud_status' => $payload['fraud_status'] ?? $lockedPayment->fraud_status,
                'raw_response' => $payload,
            ];

            $shouldUpdateStatus = ! (
                ($successPaymentExists && $mappedStatus === 'success')
                || $previousStatus === 'success'
                || (in_array($previousStatus, ['expired', 'failed', 'cancelled'], true) && $mappedStatus === 'pending')
            );

            if ($shouldUpdateStatus) {
                $update['status'] = $mappedStatus;
            }

            if ($mappedStatus === 'success') {
                $update['paid_at'] = $lockedPayment->paid_at ?: now();
            }

            if (in_array($mappedStatus, ['expired', 'failed', 'cancelled'], true)) {
                $update['expired_at'] = $mappedStatus === 'expired' ? now() : $lockedPayment->expired_at;
            }

            $lockedPayment->update($update);

            if ($successPaymentExists && $mappedStatus === 'success') {
                return $lockedPayment->fresh();
            }

            if ($previousStatus === 'success') {
                return $lockedPayment->fresh();
            }

            if (in_array($previousStatus, ['expired', 'failed', 'cancelled'], true) && $mappedStatus === 'pending') {
                return $lockedPayment->fresh();
            }

            if ($mappedStatus === 'pending') {
                $order->update(['payment_status' => 'pending']);

                return $lockedPayment->fresh();
            }

            if ($mappedStatus === 'success') {
                $this->settleOrder($order);
                $this->notifications->sendOrderEvent($order->fresh(), 'payment_success');

                return $lockedPayment->fresh();
            }

            if ($previousStatus === 'pending' && in_array($mappedStatus, ['expired', 'failed', 'cancelled'], true)) {
                $this->releaseReservedStock($order);
                $order->update(['payment_status' => $mappedStatus]);
            }

            return $lockedPayment->fresh();
        });
    }

    private function assertCanCreateAttempt(Order $order): void
    {
        if ($order->order_status === 'dibatalkan') {
            throw ValidationException::withMessages(['order' => 'Pesanan sudah dibatalkan.']);
        }

        if ($order->payment_status === 'success' || $order->payments->contains('status', 'success')) {
            throw ValidationException::withMessages(['order' => 'Pesanan sudah dibayar.']);
        }

        if ($order->payments->contains('status', 'pending')) {
            throw ValidationException::withMessages(['order' => 'Masih ada payment attempt pending.']);
        }
    }

    private function reserveStockForOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            /** @var ProductVariant $variant */
            $variant = ProductVariant::query()->lockForUpdate()->findOrFail($item->product_variant_id);

            if ($variant->status !== 'aktif' || $variant->availableStock() < $item->quantity) {
                throw ValidationException::withMessages([
                    'stock' => "Stok {$item->product_name} tidak mencukupi untuk payment attempt baru.",
                ]);
            }

            $variant->increment('reserved_stock', $item->quantity);
        }
    }

    private function releaseReservedStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            /** @var ProductVariant $variant */
            $variant = ProductVariant::query()->lockForUpdate()->findOrFail($item->product_variant_id);
            $releaseQuantity = min($item->quantity, $variant->reserved_stock);

            if ($releaseQuantity > 0) {
                $variant->decrement('reserved_stock', $releaseQuantity);
            }
        }
    }

    private function settleOrder(Order $order): void
    {
        $order->loadMissing('items');
        $canSettle = true;
        $lockedVariants = [];

        foreach ($order->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            /** @var ProductVariant $variant */
            $variant = ProductVariant::query()->lockForUpdate()->findOrFail($item->product_variant_id);
            $lockedVariants[$item->id] = $variant;

            if ($variant->reserved_stock < $item->quantity || $variant->stock < $item->quantity) {
                $canSettle = false;
            }
        }

        if (! $canSettle) {
            $order->update([
                'payment_status' => 'success',
                'order_status' => 'perlu_review_admin',
            ]);

            return;
        }

        foreach ($order->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            /** @var ProductVariant $variant */
            $variant = $lockedVariants[$item->id];
            $variant->update([
                'stock' => $variant->stock - $item->quantity,
                'reserved_stock' => $variant->reserved_stock - $item->quantity,
            ]);
        }

        $order->update([
            'payment_status' => 'success',
            'order_status' => 'dibayar',
        ]);
    }

    private function snapPayload(Order $order, string $midtransOrderId): array
    {
        return [
            'transaction_details' => [
                'order_id' => $midtransOrderId,
                'gross_amount' => (int) round((float) $order->total_amount),
            ],
            'customer_details' => [
                'first_name' => $order->customer_name,
                'email' => $order->user?->email,
                'phone' => $order->customer_phone,
            ],
            'item_details' => $this->itemDetails($order),
            'callbacks' => [
                'finish' => route('checkout.index', ['order' => $order->id, 'payment_return' => 1]),
            ],
        ];
    }

    private function itemDetails(Order $order): array
    {
        $items = $order->items->map(fn ($item) => [
            'id' => $item->variant_sku ?: 'ITEM-'.$item->id,
            'price' => (int) round((float) $item->product_price),
            'quantity' => $item->quantity,
            'name' => Str::limit(trim($item->product_name.' '.$item->variant_name), 50, ''),
        ])->values()->all();

        if ((float) $order->discount_amount > 0) {
            $items[] = [
                'id' => 'DISCOUNT',
                'price' => -1 * (int) round((float) $order->discount_amount),
                'quantity' => 1,
                'name' => 'Diskon voucher',
            ];
        }

        if ((float) $order->shipping_cost > 0) {
            $items[] = [
                'id' => 'SHIPPING',
                'price' => (int) round((float) $order->shipping_cost),
                'quantity' => 1,
                'name' => 'Ongkir internal',
            ];
        }

        return $items;
    }
}
