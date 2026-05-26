<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Midtrans\MidtransPaymentGateway;
use App\Services\Payments\PaymentAttemptService;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerOrderController extends Controller
{
    public function index(Request $request, PaymentAttemptService $payments): Response
    {
        $payments->expireOverduePendingAttempts();

        $orders = Order::query()
            ->with([
                'items' => fn ($query) => $query
                    ->select('id', 'order_id', 'product_variant_id', 'product_name', 'variant_name', 'quantity', 'subtotal')
                    ->with(['variant.images' => fn ($imageQuery) => $imageQuery
                        ->select('id', 'product_variant_id', 'file_path', 'is_primary', 'sort_order')
                        ->orderByDesc('is_primary')
                        ->orderBy('sort_order')
                        ->orderBy('id')])
                    ->orderBy('id'),
                'total',
                'delivery',
                'payments' => fn ($query) => $query->latest('attempt_number'),
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders' => $orders->through(fn (Order $order) => $this->summaryPayload($order)),
        ]);
    }

    public function show(Request $request, Order $order, MidtransPaymentGateway $midtrans, PaymentAttemptService $payments): Response
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $payments->expireOverduePendingAttempts($order);

        $order->load([
            'user:id,name,email,phone',
            'address',
            'total',
            'delivery',
            'voucherSnapshot',
            'shippingSnapshot',
            'items' => fn ($query) => $query
                ->select('id', 'order_id', 'product_variant_id', 'product_name', 'variant_name', 'variant_sku', 'variant_size', 'variant_material', 'variant_color', 'product_price', 'quantity', 'subtotal')
                ->with(['variant.images' => fn ($imageQuery) => $imageQuery
                    ->select('id', 'product_variant_id', 'file_path', 'is_primary', 'sort_order')
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order')
                    ->orderBy('id')])
                ->orderBy('id'),
            'payments' => fn ($query) => $query->latest('attempt_number'),
        ]);

        return Inertia::render('Orders/Show', [
            'order' => $this->detailPayload($order),
            'paymentGateway' => $midtrans->clientConfig(),
        ]);
    }

    private function summaryPayload(Order $order): array
    {
        $latestPayment = $order->payments->first();
        $previewItem = $order->items->first();
        $previewImage = $previewItem?->variant?->images?->first();

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'created_at' => $order->created_at?->toIso8601String(),
            'preview_product_name' => $previewItem?->product_name,
            'preview_variant_name' => $previewItem?->variant_name,
            'image_url' => MediaUrl::fromPath($previewImage?->file_path),
            'total_amount' => (float) $order->total_amount,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'shipment_summary' => $this->shipmentSummary($order),
            'shipment' => $this->shipmentPayload($order),
            'items_count' => $order->items->sum('quantity'),
            'latest_payment' => $latestPayment ? [
                'attempt_number' => $latestPayment->attempt_number,
                'status' => $latestPayment->status,
                'expired_at' => $latestPayment->expired_at?->toIso8601String(),
            ] : null,
        ];
    }

    private function detailPayload(Order $order): array
    {
        $latestPayment = $order->payments->first();
        $hasPendingPayment = $order->payments->contains('status', 'pending');
        $hasSuccessPayment = $order->payments->contains('status', 'success');
        $address = $order->address;
        $voucher = $order->voucherSnapshot;
        $shipping = $order->shippingSnapshot;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'created_at' => $order->created_at?->toIso8601String(),
            'customer_name' => $address?->recipient_name ?: $order->user?->name,
            'customer_phone' => $address?->phone ?: $order->user?->phone,
            'shipping_address' => $address?->formatted_address,
            'shipping_city' => $address?->city,
            'shipping_district' => $address?->district,
            'shipping_postal_code' => $address?->postal_code,
            'shipping_latitude' => $address?->latitude === null ? null : (float) $address->latitude,
            'shipping_longitude' => $address?->longitude === null ? null : (float) $address->longitude,
            'shipping_note' => collect([$address?->detail, $order->customer_note])->filter()->join("\n\n") ?: null,
            'store' => $shipping ? [
                'id' => $shipping->shipping_setting_id,
                'name' => $shipping->origin_name,
                'origin_address' => $shipping->origin_address,
                'origin_latitude' => $shipping->origin_latitude === null ? null : (float) $shipping->origin_latitude,
                'origin_longitude' => $shipping->origin_longitude === null ? null : (float) $shipping->origin_longitude,
                'shipping_cost_per_km' => $shipping->shipping_cost_per_km === null ? null : (float) $shipping->shipping_cost_per_km,
                'distance_km' => $shipping->distance_km === null ? null : (float) $shipping->distance_km,
                'billable_distance_km' => $shipping->billable_distance_km === null ? null : (float) $shipping->billable_distance_km,
                'shipping_cost' => $shipping->shipping_cost === null ? null : (float) $shipping->shipping_cost,
                'duration_seconds' => $shipping->duration_seconds,
                'distance_provider' => $shipping->distance_provider,
                'route_geometry' => $shipping->route_geometry ?? [],
            ] : null,
            'subtotal_amount' => (float) $order->subtotal_amount,
            'discount_amount' => (float) $order->discount_amount,
            'shipping_cost' => (float) $order->shipping_cost,
            'total_amount' => (float) $order->total_amount,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'shipment_summary' => $this->shipmentSummary($order),
            'voucher' => $voucher ? [
                'code' => $voucher->voucher_code,
                'name' => $voucher->voucher_name,
                'status' => $order->voucher?->status,
            ] : null,
            'items' => $order->items->map(function ($item) {
                $image = $item->variant?->images?->first();

                return [
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'variant_sku' => $item->variant_sku,
                    'variant_size' => $item->variant_size,
                    'variant_material' => $item->variant_material,
                    'variant_color' => $item->variant_color,
                    'image_url' => MediaUrl::fromPath($image?->file_path),
                    'product_price' => (float) $item->product_price,
                    'quantity' => $item->quantity,
                    'subtotal' => (float) $item->subtotal,
                ];
            })->values(),
            'payment' => $latestPayment ? $this->paymentPayload($latestPayment) : null,
            'payments' => $order->payments->map(fn (Payment $payment) => $this->paymentPayload($payment))->values(),
            'shipment' => $this->shipmentPayload($order),
            'timeline' => $this->timeline($order),
            'can_create_payment_attempt' => ! $hasPendingPayment && ! $hasSuccessPayment,
            'can_open_payment' => $latestPayment?->status === 'pending'
                && $latestPayment->snap_token
                && (! $latestPayment->expired_at || $latestPayment->expired_at->isFuture()),
        ];
    }

    private function paymentPayload(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'attempt_number' => $payment->attempt_number,
            'payment_type' => $payment->payment_type,
            'status' => $payment->status,
            'transaction_status' => $payment->transaction_status,
            'gross_amount' => (float) $payment->gross_amount,
            'snap_token' => $payment->snap_token,
            'redirect_url' => $payment->redirect_url,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'expired_at' => $payment->expired_at?->toIso8601String(),
        ];
    }

    private function timeline(Order $order): array
    {
        $paymentStepStatus = match (true) {
            in_array($order->payment_status, ['success'], true) => 'completed',
            in_array($order->payment_status, ['failed', 'expired', 'cancelled'], true) => 'blocked',
            default => 'current',
        };

        $items = [
            $this->timelineItem('Pesanan dibuat', 'Order diterima sistem.', 'completed'),
            $this->timelineItem(
                'Pembayaran berhasil',
                $order->payment_status === 'success' ? 'Pembayaran valid diterima.' : 'Menunggu pembayaran.',
                $paymentStepStatus
            ),
            $this->timelineItem('Pesanan diproses', 'Tim toko menyiapkan sofa.', in_array($order->order_status, ['diproses', 'dalam_perjalanan', 'barang_diterima'], true) ? 'completed' : 'pending'),
        ];

        return [
            ...$items,
            ...$this->shipmentTimeline($order),
        ];
    }

    private function shipmentTimeline(Order $order): array
    {
        if (! in_array($order->payment_status, ['success'], true)) {
            return [
                $this->timelineItem('Dalam perjalanan', 'Pesanan akan masuk perjalanan setelah pembayaran berhasil.', 'pending'),
                $this->timelineItem('Barang diterima', 'Pesanan belum diterima pelanggan.', 'pending'),
            ];
        }

        $status = $order->order_status;

        return [
            $this->timelineItem(
                'Dalam perjalanan',
                $order->driver_name
                    ? 'Petugas: '.$order->driver_name.($order->driver_phone ? ' ('.$order->driver_phone.')' : '').'.'
                    : 'Pesanan sedang dalam perjalanan.',
                $status === 'barang_diterima' ? 'completed' : ($status === 'dalam_perjalanan' ? 'current' : 'pending')
            ),
            $this->timelineItem(
                'Barang diterima',
                $order->delivery_delivered_at
                    ? 'Pesanan diterima pada '.$order->delivery_delivered_at->translatedFormat('d F Y').'.'
                    : 'Pesanan sudah diterima pelanggan.',
                $status === 'barang_diterima' ? 'completed' : 'pending'
            ),
        ];
    }

    private function shipmentSummary(Order $order): string
    {
        return match ($order->order_status) {
            'dalam_perjalanan' => $order->driver_name
                ? 'Dalam perjalanan bersama '.$order->driver_name.'.'
                : 'Pesanan sedang dalam perjalanan.',
            'barang_diterima' => $order->delivery_delivered_at
                ? 'Barang diterima '.$order->delivery_delivered_at->translatedFormat('d F Y').'.'
                : 'Barang sudah diterima.',
            default => 'Pesanan sedang diproses.',
        };
    }

    private function timelineItem(string $label, string $description, string $state): array
    {
        return compact('label', 'description', 'state');
    }

    private function shipmentPayload(Order $order): ?array
    {
        if (! $order->delivery_scheduled_at && ! $order->driver_name) {
            return null;
        }

        return [
            'scheduled_at' => $order->delivery_scheduled_at?->toIso8601String(),
            'delivered_at' => $order->delivery_delivered_at?->toIso8601String(),
            'driver_name' => $order->driver_name,
            'driver_phone' => $order->driver_phone,
            'vehicle_note' => $order->vehicle_note,
            'shipping_note' => $order->delivery_note,
        ];
    }
}
