<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\Orders\OrderStatusTransitionService;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private readonly OrderStatusTransitionService $transitions)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'order_status' => ['nullable', Rule::in(['', ...OrderStatusTransitionService::STATUSES])],
            'payment_status' => ['nullable', Rule::in(['', 'pending', 'success', 'failed', 'expired', 'cancelled'])],
            'shipment_status' => ['nullable', Rule::in(['', 'belum_dijadwalkan', 'dijadwalkan', 'dalam_pengiriman', 'terkirim', 'gagal_dikirim'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $orders = Order::query()
            ->with(['user:id,name,email', 'shipment:id,order_id,status', 'payments' => fn ($query) => $query->latest('attempt_number')])
            ->withCount('items')
            ->when($filters['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('order_number', 'like', "%{$keyword}%")
                        ->orWhere('customer_name', 'like', "%{$keyword}%")
                        ->orWhere('customer_phone', 'like', "%{$keyword}%")
                        ->orWhereHas('user', fn ($query) => $query->where('email', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['order_status'] ?? null, fn ($query, string $status) => $query->where('order_status', $status))
            ->when($filters['payment_status'] ?? null, fn ($query, string $status) => $query->where('payment_status', $status))
            ->when($filters['shipment_status'] ?? null, function ($query, string $status) {
                if ($status === 'belum_dijadwalkan') {
                    $query->where(function ($query) {
                        $query->whereDoesntHave('shipment')
                            ->orWhereHas('shipment', fn ($query) => $query->where('status', 'belum_dijadwalkan'));
                    });

                    return;
                }

                $query->whereHas('shipment', fn ($query) => $query->where('status', $status));
            })
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Order $order) => $this->summaryPayload($order));

        return Inertia::render('Admin/Orders/Index', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Pesanan', 'href' => route('admin.orders.index')],
            ],
            'orders' => $orders,
            'filters' => [
                'keyword' => $filters['keyword'] ?? '',
                'order_status' => $filters['order_status'] ?? '',
                'payment_status' => $filters['payment_status'] ?? '',
                'shipment_status' => $filters['shipment_status'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'orderStatusOptions' => $this->orderStatusOptions(true),
            'paymentStatusOptions' => $this->paymentStatusOptions(true),
            'shipmentStatusOptions' => $this->shipmentStatusOptions(true),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $order->load([
            'user:id,name,email',
            'items.product:id,name',
            'items.variant:id,sku,variant_name,stock,reserved_stock,status',
            'payments' => fn ($query) => $query->latest('attempt_number'),
            'shipment',
            'voucher:id,code,name,status',
            'store:id,name',
        ]);
        $order->loadCount('items');

        return Inertia::render('Admin/Orders/Show', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Pesanan', 'href' => route('admin.orders.index')],
                ['label' => $order->order_number, 'href' => route('admin.orders.show', $order)],
            ],
            'order' => $this->detailPayload($order),
            'orderStatusOptions' => $this->orderStatusOptions(false),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'order_status' => ['required', Rule::in(OrderStatusTransitionService::STATUSES)],
        ]);

        $this->transitions->updateByAdmin($order, $data['order_status']);

        return back()->with('success', 'Status pesanan diperbarui.');
    }

    private function summaryPayload(Order $order): array
    {
        $latestPayment = $order->payments->first();

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'created_at' => $order->created_at?->toIso8601String(),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->user?->email,
            'total_amount' => (float) $order->total_amount,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'shipment_status' => $order->shipment?->status ?? 'belum_dijadwalkan',
            'items_count' => $order->items_count,
            'latest_payment' => $latestPayment ? [
                'attempt_number' => $latestPayment->attempt_number,
                'status' => $latestPayment->status,
                'midtrans_order_id' => $latestPayment->midtrans_order_id,
            ] : null,
        ];
    }

    private function detailPayload(Order $order): array
    {
        return [
            ...$this->summaryPayload($order),
            'shipping_address' => $order->shipping_address,
            'shipping_city' => $order->shipping_city,
            'shipping_district' => $order->shipping_district,
            'shipping_postal_code' => $order->shipping_postal_code,
            'shipping_latitude' => (float) $order->shipping_latitude,
            'shipping_longitude' => (float) $order->shipping_longitude,
            'shipping_note' => $order->shipping_note,
            'subtotal_amount' => (float) $order->subtotal_amount,
            'discount_amount' => (float) $order->discount_amount,
            'shipping_cost' => (float) $order->shipping_cost,
            'voucher' => $order->voucher ? [
                'code' => $order->voucher->code,
                'name' => $order->voucher->name,
                'status' => $order->voucher->status,
            ] : null,
            'store' => $order->store ? [
                'name' => $order->store->name,
            ] : null,
            'items' => $order->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'variant_sku' => $item->variant_sku,
                'variant_size' => $item->variant_size,
                'variant_material' => $item->variant_material,
                'variant_color' => $item->variant_color,
                'product_price' => (float) $item->product_price,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
                'current_variant' => $item->variant ? [
                    'stock' => $item->variant->stock,
                    'reserved_stock' => $item->variant->reserved_stock,
                    'status' => $item->variant->status,
                ] : null,
            ])->values(),
            'payments' => $order->payments->map(fn (Payment $payment) => $this->paymentPayload($payment, true))->values(),
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
        ];
    }

    private function paymentPayload(Payment $payment, bool $withRaw = false): array
    {
        $payload = [
            'id' => $payment->id,
            'attempt_number' => $payment->attempt_number,
            'midtrans_order_id' => $payment->midtrans_order_id,
            'midtrans_transaction_id' => $payment->midtrans_transaction_id,
            'payment_type' => $payment->payment_type,
            'status' => $payment->status,
            'transaction_status' => $payment->transaction_status,
            'fraud_status' => $payment->fraud_status,
            'gross_amount' => (float) $payment->gross_amount,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'expired_at' => $payment->expired_at?->toIso8601String(),
            'created_at' => $payment->created_at?->toIso8601String(),
        ];

        if ($withRaw) {
            $payload['raw_response_preview'] = $this->limitedRawResponse($payment->raw_response);
        }

        return $payload;
    }

    private function limitedRawResponse(?array $raw): ?array
    {
        if (! $raw) {
            return null;
        }

        return collect($raw)
            ->only([
                'transaction_status',
                'fraud_status',
                'status_code',
                'status_message',
                'payment_type',
                'transaction_time',
                'order_id',
                'gross_amount',
            ])
            ->all();
    }

    private function timeline(Order $order): array
    {
        if ($order->order_status === 'dibatalkan') {
            return [
                ['label' => 'Pesanan dibuat', 'description' => 'Order diterima sistem.', 'state' => 'completed'],
                ['label' => 'Pesanan dibatalkan', 'description' => 'Pesanan tidak dilanjutkan.', 'state' => 'current'],
            ];
        }

        return [
            ['label' => 'Pesanan dibuat', 'description' => 'Order diterima sistem.', 'state' => 'completed'],
            ['label' => $order->order_status === 'perlu_review_admin' ? 'Perlu review admin' : 'Pembayaran berhasil', 'description' => $order->payment_status === 'success' ? 'Pembayaran valid diterima.' : 'Menunggu pembayaran Midtrans.', 'state' => $order->payment_status === 'success' || $order->order_status === 'perlu_review_admin' ? 'completed' : 'current'],
            ['label' => 'Pesanan diproses', 'description' => 'Tim toko menyiapkan sofa.', 'state' => in_array($order->order_status, ['diproses', 'dikirim', 'selesai'], true) ? 'completed' : 'pending'],
            ['label' => 'Pesanan dikirim', 'description' => 'Pengiriman internal toko berjalan.', 'state' => in_array($order->order_status, ['dikirim', 'selesai'], true) ? 'completed' : 'pending'],
            ['label' => 'Pesanan selesai', 'description' => 'Pesanan diterima pelanggan.', 'state' => $order->order_status === 'selesai' ? 'completed' : 'pending'],
        ];
    }

    private function orderStatusOptions(bool $withAll): array
    {
        $options = collect(OrderStatusTransitionService::STATUSES)
            ->map(fn (string $status) => ['value' => $status, 'label' => str_replace('_', ' ', $status)]);

        return $withAll ? $options->prepend(['value' => '', 'label' => 'Semua status'])->values()->all() : $options->values()->all();
    }

    private function paymentStatusOptions(bool $withAll): array
    {
        $options = collect(['pending', 'success', 'failed', 'expired', 'cancelled'])
            ->map(fn (string $status) => ['value' => $status, 'label' => str_replace('_', ' ', $status)]);

        return $withAll ? $options->prepend(['value' => '', 'label' => 'Semua pembayaran'])->values()->all() : $options->values()->all();
    }

    private function shipmentStatusOptions(bool $withAll): array
    {
        $options = collect(['belum_dijadwalkan', 'dijadwalkan', 'dalam_pengiriman', 'terkirim', 'gagal_dikirim'])
            ->map(fn (string $status) => ['value' => $status, 'label' => str_replace('_', ' ', $status)]);

        return $withAll ? $options->prepend(['value' => '', 'label' => 'Semua pengiriman'])->values()->all() : $options->values()->all();
    }
}
