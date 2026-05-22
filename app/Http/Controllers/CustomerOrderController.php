<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Midtrans\MidtransPaymentGateway;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->with(['items:id,order_id,product_name,variant_name,quantity,subtotal', 'payments' => fn ($query) => $query->latest('attempt_number'), 'shipment:id,order_id,status,scheduled_at,delivered_at,driver_name'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders' => $orders->through(fn (Order $order) => $this->summaryPayload($order)),
        ]);
    }

    public function show(Request $request, Order $order, MidtransPaymentGateway $midtrans): Response
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load([
            'items:id,order_id,product_name,variant_name,variant_sku,variant_size,variant_material,variant_color,product_price,quantity,subtotal',
            'payments' => fn ($query) => $query->latest('attempt_number'),
            'shipment:id,order_id,status,scheduled_at,delivered_at,driver_name,driver_phone,vehicle_note,shipping_note',
            'voucher:id,code,name,status',
            'store:id,name',
        ]);

        return Inertia::render('Orders/Show', [
            'order' => $this->detailPayload($order),
            'midtrans' => $midtrans->clientConfig(),
        ]);
    }

    private function summaryPayload(Order $order): array
    {
        $latestPayment = $order->payments->first();
        $shipmentStatus = $order->shipment?->status ?? 'belum_dijadwalkan';

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'created_at' => $order->created_at?->toIso8601String(),
            'total_amount' => (float) $order->total_amount,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'shipment_status' => $shipmentStatus,
            'shipment_label' => $this->shipmentLabel($shipmentStatus),
            'shipment_summary' => $this->shipmentSummary($order),
            'shipment' => $order->shipment ? [
                'status' => $order->shipment->status,
                'scheduled_at' => $order->shipment->scheduled_at?->toIso8601String(),
                'delivered_at' => $order->shipment->delivered_at?->toIso8601String(),
            ] : null,
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
        $shipmentStatus = $order->shipment?->status ?? 'belum_dijadwalkan';

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'created_at' => $order->created_at?->toIso8601String(),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'shipping_address' => $order->shipping_address,
            'shipping_city' => $order->shipping_city,
            'shipping_district' => $order->shipping_district,
            'shipping_postal_code' => $order->shipping_postal_code,
            'shipping_note' => $order->shipping_note,
            'store' => $order->store ? [
                'id' => $order->store->id,
                'name' => $order->store->name,
            ] : null,
            'subtotal_amount' => (float) $order->subtotal_amount,
            'discount_amount' => (float) $order->discount_amount,
            'shipping_cost' => (float) $order->shipping_cost,
            'total_amount' => (float) $order->total_amount,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'shipment_status' => $shipmentStatus,
            'shipment_label' => $this->shipmentLabel($shipmentStatus),
            'shipment_summary' => $this->shipmentSummary($order),
            'voucher' => $order->voucher ? [
                'code' => $order->voucher->code,
                'name' => $order->voucher->name,
                'status' => $order->voucher->status,
            ] : null,
            'items' => $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'variant_sku' => $item->variant_sku,
                'variant_size' => $item->variant_size,
                'variant_material' => $item->variant_material,
                'variant_color' => $item->variant_color,
                'product_price' => (float) $item->product_price,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ])->values(),
            'payment' => $latestPayment ? $this->paymentPayload($latestPayment) : null,
            'payments' => $order->payments->map(fn (Payment $payment) => $this->paymentPayload($payment))->values(),
            'shipment' => $order->shipment ? [
                'status' => $order->shipment->status,
                'scheduled_at' => $order->shipment->scheduled_at?->toIso8601String(),
                'delivered_at' => $order->shipment->delivered_at?->toIso8601String(),
                'driver_name' => $order->shipment->driver_name,
                'driver_phone' => $order->shipment->driver_phone,
                'vehicle_note' => $order->shipment->vehicle_note,
                'shipping_note' => $order->shipment->shipping_note,
            ] : null,
            'timeline' => $this->timeline($order),
            'can_create_payment_attempt' => ! $hasPendingPayment && ! $hasSuccessPayment && $order->order_status !== 'dibatalkan',
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
            'midtrans_order_id' => $payment->midtrans_order_id,
            'midtrans_transaction_id' => $payment->midtrans_transaction_id,
            'payment_type' => $payment->payment_type,
            'status' => $payment->status,
            'transaction_status' => $payment->transaction_status,
            'fraud_status' => $payment->fraud_status,
            'gross_amount' => (float) $payment->gross_amount,
            'snap_token' => $payment->snap_token,
            'redirect_url' => $payment->redirect_url,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'expired_at' => $payment->expired_at?->toIso8601String(),
        ];
    }

    private function timeline(Order $order): array
    {
        if ($order->order_status === 'dibatalkan') {
            return [
                $this->timelineItem('Pesanan dibuat', 'Order diterima sistem.', 'completed'),
                $this->timelineItem('Pesanan dibatalkan', 'Pesanan tidak dilanjutkan.', 'current'),
            ];
        }

        $paymentStepStatus = match (true) {
            $order->order_status === 'perlu_review_admin' => 'current',
            in_array($order->payment_status, ['success'], true) => 'completed',
            in_array($order->payment_status, ['failed', 'expired', 'cancelled'], true) => 'blocked',
            default => 'current',
        };

        $items = [
            $this->timelineItem('Pesanan dibuat', 'Order diterima sistem.', 'completed'),
            $this->timelineItem(
                $order->order_status === 'perlu_review_admin' ? 'Perlu review admin' : 'Pembayaran berhasil',
                $order->order_status === 'perlu_review_admin'
                    ? 'Pembayaran diterima, stok atau order sedang dicek admin.'
                    : ($order->payment_status === 'success' ? 'Pembayaran valid diterima.' : 'Menunggu pembayaran Midtrans.'),
                $paymentStepStatus
            ),
            $this->timelineItem('Pesanan diproses', 'Tim toko menyiapkan sofa.', in_array($order->order_status, ['diproses', 'dikirim', 'selesai'], true) ? 'completed' : 'pending'),
        ];

        return [
            ...$items,
            ...$this->shipmentTimeline($order),
        ];
    }

    private function shipmentTimeline(Order $order): array
    {
        $status = $order->shipment?->status ?? 'belum_dijadwalkan';

        if (! in_array($order->payment_status, ['success'], true)) {
            return [
                $this->timelineItem('Pengiriman belum dijadwalkan', 'Pengiriman akan dijadwalkan setelah pembayaran berhasil.', 'pending'),
                $this->timelineItem('Pengiriman dijadwalkan', 'Jadwal pengiriman akan tampil setelah admin menentukan waktu kirim.', 'pending'),
                $this->timelineItem('Dalam pengiriman', 'Pesanan belum dibawa oleh petugas pengiriman.', 'pending'),
                $this->timelineItem('Terkirim', 'Pesanan belum diterima pelanggan.', 'pending'),
                $this->timelineItem('Gagal dikirim', 'Tidak ada kendala pengiriman saat ini.', 'pending'),
            ];
        }

        $rank = [
            'belum_dijadwalkan' => 0,
            'dijadwalkan' => 1,
            'dalam_pengiriman' => 2,
            'terkirim' => 3,
        ];
        $currentRank = $rank[$status] ?? 0;

        return [
            $this->timelineItem(
                'Pengiriman belum dijadwalkan',
                'Admin belum menentukan jadwal pengiriman.',
                $status === 'belum_dijadwalkan' ? 'current' : 'completed'
            ),
            $this->timelineItem(
                'Pengiriman dijadwalkan',
                $order->shipment?->scheduled_at
                    ? 'Jadwal pengiriman: '.$order->shipment->scheduled_at->translatedFormat('d F Y H.i').'.'
                    : 'Jadwal pengiriman akan tampil setelah admin menentukan waktu kirim.',
                $this->shipmentStepState($status, 'dijadwalkan', $currentRank, $rank)
            ),
            $this->timelineItem(
                'Dalam pengiriman',
                $order->shipment?->driver_name
                    ? 'Petugas: '.$order->shipment->driver_name.($order->shipment->driver_phone ? ' ('.$order->shipment->driver_phone.')' : '').'.'
                    : 'Pesanan sedang dibawa oleh petugas pengiriman.',
                $this->shipmentStepState($status, 'dalam_pengiriman', $currentRank, $rank)
            ),
            $this->timelineItem(
                'Terkirim',
                $order->shipment?->delivered_at
                    ? 'Pesanan diterima pada '.$order->shipment->delivered_at->translatedFormat('d F Y H.i').'.'
                    : 'Pesanan sudah diterima pelanggan.',
                $this->shipmentStepState($status, 'terkirim', $currentRank, $rank)
            ),
            $this->timelineItem(
                'Gagal dikirim',
                'Pengiriman belum berhasil. Tim toko akan menghubungi atau menjadwalkan ulang.',
                $status === 'gagal_dikirim' ? 'blocked' : 'pending'
            ),
        ];
    }

    private function shipmentStepState(string $currentStatus, string $stepStatus, int $currentRank, array $rank): string
    {
        if ($currentStatus === 'gagal_dikirim') {
            return 'pending';
        }

        if ($currentStatus === $stepStatus) {
            return $stepStatus === 'terkirim' ? 'completed' : 'current';
        }

        return $currentRank > ($rank[$stepStatus] ?? 0) ? 'completed' : 'pending';
    }

    private function shipmentLabel(string $status): string
    {
        return [
            'belum_dijadwalkan' => 'Belum dijadwalkan',
            'dijadwalkan' => 'Dijadwalkan',
            'dalam_pengiriman' => 'Dalam pengiriman',
            'terkirim' => 'Terkirim',
            'gagal_dikirim' => 'Gagal dikirim',
        ][$status] ?? str_replace('_', ' ', $status);
    }

    private function shipmentSummary(Order $order): string
    {
        $shipment = $order->shipment;
        $status = $shipment?->status ?? 'belum_dijadwalkan';

        return match ($status) {
            'belum_dijadwalkan' => 'Pengiriman belum dijadwalkan.',
            'dijadwalkan' => $shipment?->scheduled_at
                ? 'Dijadwalkan '.$shipment->scheduled_at->translatedFormat('d F Y H.i').'.'
                : 'Pengiriman sudah dijadwalkan.',
            'dalam_pengiriman' => $shipment?->driver_name
                ? 'Sedang dikirim oleh '.$shipment->driver_name.'.'
                : 'Pesanan sedang dalam pengiriman.',
            'terkirim' => $shipment?->delivered_at
                ? 'Terkirim '.$shipment->delivered_at->translatedFormat('d F Y H.i').'.'
                : 'Pesanan sudah terkirim.',
            'gagal_dikirim' => 'Pengiriman belum berhasil dan perlu ditindaklanjuti.',
            default => $this->shipmentLabel($status),
        };
    }

    private function timelineItem(string $label, string $description, string $state): array
    {
        return compact('label', 'description', 'state');
    }
}
