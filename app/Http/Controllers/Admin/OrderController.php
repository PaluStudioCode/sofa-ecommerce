<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\Orders\OrderStatusTransitionService;
use App\Services\Payments\PaymentAttemptService;
use App\Support\MediaUrl;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    public function index(Request $request, PaymentAttemptService $payments): Response
    {
        $payments->expireOverduePendingAttempts();

        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'order_status' => ['nullable', Rule::in(['', ...OrderStatusTransitionService::STATUSES])],
            'payment_status' => ['nullable', Rule::in(['', 'pending', 'success', 'failed', 'expired', 'cancelled'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $orders = Order::query()
            ->with(['user:id,name,email,phone', 'address', 'total', 'delivery', 'payments' => fn ($query) => $query->latest('attempt_number')])
            ->withCount('items')
            ->when($filters['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('order_number', 'like', "%{$keyword}%")
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%"))
                        ->orWhereHas('address', fn ($query) => $query
                            ->where('recipient_name', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%")
                            ->orWhere('formatted_address', 'like', "%{$keyword}%"))
                        ->orWhereHas('payments', fn ($query) => $query
                            ->where('midtrans_order_id', 'like', "%{$keyword}%")
                            ->orWhere('midtrans_transaction_id', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['order_status'] ?? null, fn ($query, string $status) => $query->where('order_status', $status))
            ->when($filters['payment_status'] ?? null, fn ($query, string $status) => $query->whereHas('payments', fn ($query) => $query->where('status', $status)))
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
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'orderStatusOptions' => $this->orderStatusOptions(true),
            'paymentStatusOptions' => $this->paymentStatusOptions(true),
        ]);
    }

    public function show(Request $request, Order $order, PaymentAttemptService $payments): Response
    {
        $payments->expireOverduePendingAttempts($order);

        $order->load([
            'user:id,name,email,phone',
            'address',
            'total',
            'delivery',
            'voucherSnapshot',
            'shippingSnapshot',
            'items' => fn ($query) => $query
                ->with([
                    'variant' => fn ($variantQuery) => $variantQuery
                        ->select('id', 'sku', 'variant_name', 'stock', 'reserved_stock', 'status')
                        ->with(['images' => fn ($imageQuery) => $imageQuery
                            ->select('id', 'product_variant_id', 'file_path', 'is_primary', 'sort_order')
                            ->orderByDesc('is_primary')
                            ->orderBy('sort_order')
                            ->orderBy('id'),
                        ]),
                ])
                ->orderBy('id'),
            'payments' => fn ($query) => $query->latest('attempt_number'),
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
        ]);
    }

    private function summaryPayload(Order $order): array
    {
        $latestPayment = $order->payments->first();
        $address = $order->address;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'created_at' => $order->created_at?->toIso8601String(),
            'customer_name' => $address?->recipient_name ?: $order->user?->name,
            'customer_phone' => $address?->phone ?: $order->user?->phone,
            'customer_email' => $order->user?->email,
            'total_amount' => (float) $order->total_amount,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'can_manage_shipment' => $order->payment_status === 'success'
                && in_array($order->order_status, ['diproses', 'dalam_perjalanan', 'barang_diterima'], true),
            'allowed_order_statuses' => $this->allowedOrderStatuses($order),
            'items_count' => $order->items_count,
            'latest_payment' => $latestPayment ? [
                'id' => $latestPayment->id,
                'attempt_number' => $latestPayment->attempt_number,
                'status' => $latestPayment->status,
                'midtrans_order_id' => $latestPayment->midtrans_order_id,
                'midtrans_transaction_id' => $latestPayment->midtrans_transaction_id,
                'payment_type' => $latestPayment->payment_type,
            ] : null,
        ];
    }

    private function detailPayload(Order $order): array
    {
        $address = $order->address;
        $voucher = $order->voucherSnapshot;
        $shipping = $order->shippingSnapshot;

        return [
            ...$this->summaryPayload($order),
            'shipping_address' => $address?->formatted_address,
            'shipping_city' => $address?->city,
            'shipping_district' => $address?->district,
            'shipping_postal_code' => $address?->postal_code,
            'shipping_latitude' => $address?->latitude === null ? null : (float) $address->latitude,
            'shipping_longitude' => $address?->longitude === null ? null : (float) $address->longitude,
            'shipping_note' => collect([$address?->detail, $order->customer_note])->filter()->join("\n\n") ?: null,
            'subtotal_amount' => (float) $order->subtotal_amount,
            'discount_amount' => (float) $order->discount_amount,
            'shipping_cost' => (float) $order->shipping_cost,
            'voucher' => $voucher ? [
                'code' => $voucher->voucher_code,
                'name' => $voucher->voucher_name,
                'status' => $order->voucher?->status,
            ] : null,
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
            'items' => $order->items->map(function (OrderItem $item) {
                $image = $item->variant?->images?->first();

                return [
                    'id' => $item->id,
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
                    'current_variant' => $item->variant ? [
                        'stock' => $item->variant->stock,
                        'reserved_stock' => $item->variant->reserved_stock,
                        'status' => $item->variant->status,
                    ] : null,
                ];
            })->values(),
            'payments' => $order->payments->map(fn (Payment $payment) => $this->paymentPayload($payment, true))->values(),
            'shipment' => $this->shipmentPayload($order),
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
        $paymentStepStatus = match (true) {
            $order->payment_status === 'success' => 'completed',
            in_array($order->payment_status, ['failed', 'expired', 'cancelled'], true) => 'blocked',
            default => 'current',
        };

        return [
            ['label' => 'Pesanan dibuat', 'description' => 'Order diterima sistem.', 'state' => 'completed'],
            ['label' => 'Pembayaran berhasil', 'description' => $order->payment_status === 'success' ? 'Pembayaran valid diterima.' : 'Menunggu pembayaran Midtrans.', 'state' => $paymentStepStatus],
            ['label' => 'Pesanan diproses', 'description' => 'Tim toko menyiapkan sofa.', 'state' => in_array($order->order_status, ['diproses', 'dalam_perjalanan', 'barang_diterima'], true) ? 'completed' : 'pending'],
            ['label' => 'Dalam perjalanan', 'description' => 'Pengiriman internal toko berjalan.', 'state' => in_array($order->order_status, ['dalam_perjalanan', 'barang_diterima'], true) ? 'completed' : 'pending'],
            ['label' => 'Barang diterima', 'description' => 'Pesanan diterima pelanggan.', 'state' => $order->order_status === 'barang_diterima' ? 'completed' : 'pending'],
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

    private function allowedOrderStatuses(Order $order): array
    {
        return array_values(array_unique([
            $order->order_status,
            ...app(OrderStatusTransitionService::class)->nextStatusesForAdmin($order),
        ]));
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

    public function export(Request $request)
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'order_status' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        return Excel::download(new OrdersExport($filters), 'Laporan_Pesanan.xlsx');
    }
}
