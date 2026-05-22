<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipment;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    public function orders(Request $request): Response
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $orders = Order::query()
            ->with(['user:id,name,email', 'shipment:id,order_id,status'])
            ->withCount('items')
            ->when($filters['keyword'] ?? null, fn ($query, string $keyword) => $this->orderKeyword($query, $keyword))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('order_status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Order $order) => $this->orderSummary($order));

        return Inertia::render('Owner/Monitoring/Orders', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => $this->breadcrumbs('Pesanan'),
            'orders' => $orders,
            'filters' => [
                'keyword' => $filters['keyword'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'statusOptions' => $this->orderStatusOptions(),
        ]);
    }

    public function orderShow(Request $request, Order $order): Response
    {
        $order->load([
            'user:id,name,email',
            'items.variant:id,stock,reserved_stock,status',
            'payments' => fn ($query) => $query->latest('attempt_number'),
            'shipment',
            'voucher:id,code,name,status',
            'store:id,name',
        ]);
        $order->loadCount('items');

        return Inertia::render('Owner/Monitoring/OrderShow', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Pesanan', 'href' => route('owner.monitoring.orders')],
                ['label' => $order->order_number, 'href' => route('owner.monitoring.orders.show', $order)],
            ],
            'order' => $this->orderDetail($order),
        ]);
    }

    public function payments(Request $request): Response
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['', 'pending', 'success', 'failed', 'expired', 'cancelled'])],
        ]);

        $payments = Payment::query()
            ->with('order:id,order_number,customer_name,total_amount,order_status,payment_status')
            ->when($filters['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('midtrans_order_id', 'like', "%{$keyword}%")
                        ->orWhere('midtrans_transaction_id', 'like', "%{$keyword}%")
                        ->orWhereHas('order', fn ($query) => $query
                            ->where('order_number', 'like', "%{$keyword}%")
                            ->orWhere('customer_name', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Payment $payment) => [
                'id' => $payment->id,
                'attempt_number' => $payment->attempt_number,
                'midtrans_order_id' => $payment->midtrans_order_id,
                'midtrans_transaction_id' => $payment->midtrans_transaction_id,
                'payment_type' => $payment->payment_type,
                'status' => $payment->status,
                'gross_amount' => (float) $payment->gross_amount,
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'expired_at' => $payment->expired_at?->toIso8601String(),
                'order' => $payment->order ? [
                    'id' => $payment->order->id,
                    'order_number' => $payment->order->order_number,
                    'customer_name' => $payment->order->customer_name,
                    'total_amount' => (float) $payment->order->total_amount,
                    'order_status' => $payment->order->order_status,
                    'payment_status' => $payment->order->payment_status,
                ] : null,
            ]);

        return Inertia::render('Owner/Monitoring/Payments', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => $this->breadcrumbs('Pembayaran'),
            'payments' => $payments,
            'filters' => [
                'keyword' => $filters['keyword'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'statusOptions' => $this->paymentStatusOptions(),
        ]);
    }

    public function shipments(Request $request): Response
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $shipments = Shipment::query()
            ->with('order:id,order_number,customer_name,customer_phone,shipping_address,total_amount,order_status,payment_status')
            ->when($filters['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('driver_name', 'like', "%{$keyword}%")
                        ->orWhereHas('order', fn ($query) => $query
                            ->where('order_number', 'like', "%{$keyword}%")
                            ->orWhere('customer_name', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Shipment $shipment) => [
                'id' => $shipment->id,
                'status' => $shipment->status,
                'scheduled_at' => $shipment->scheduled_at?->toIso8601String(),
                'delivered_at' => $shipment->delivered_at?->toIso8601String(),
                'driver_name' => $shipment->driver_name,
                'driver_phone' => $shipment->driver_phone,
                'vehicle_note' => $shipment->vehicle_note,
                'shipping_note' => $shipment->shipping_note,
                'order' => $shipment->order ? [
                    'id' => $shipment->order->id,
                    'order_number' => $shipment->order->order_number,
                    'customer_name' => $shipment->order->customer_name,
                    'customer_phone' => $shipment->order->customer_phone,
                    'shipping_address' => $shipment->order->shipping_address,
                    'total_amount' => (float) $shipment->order->total_amount,
                    'order_status' => $shipment->order->order_status,
                    'payment_status' => $shipment->order->payment_status,
                ] : null,
            ]);

        return Inertia::render('Owner/Monitoring/Shipments', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => $this->breadcrumbs('Pengiriman'),
            'shipments' => $shipments,
            'filters' => [
                'keyword' => $filters['keyword'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'statusOptions' => $this->shipmentStatusOptions(),
        ]);
    }

    private function orderKeyword($query, string $keyword): void
    {
        $query->where(function ($query) use ($keyword) {
            $query->where('order_number', 'like', "%{$keyword}%")
                ->orWhere('customer_name', 'like', "%{$keyword}%")
                ->orWhere('customer_phone', 'like', "%{$keyword}%")
                ->orWhereHas('user', fn ($query) => $query->where('email', 'like', "%{$keyword}%"));
        });
    }

    private function orderSummary(Order $order): array
    {
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
        ];
    }

    private function orderDetail(Order $order): array
    {
        return [
            ...$this->orderSummary($order),
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
            'store' => $order->store ? ['name' => $order->store->name] : null,
            'items' => $order->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'variant_sku' => $item->variant_sku,
                'product_price' => (float) $item->product_price,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ])->values(),
            'payments' => $order->payments->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'attempt_number' => $payment->attempt_number,
                'midtrans_order_id' => $payment->midtrans_order_id,
                'status' => $payment->status,
                'gross_amount' => (float) $payment->gross_amount,
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ])->values(),
            'shipment' => $order->shipment ? [
                'status' => $order->shipment->status,
                'scheduled_at' => $order->shipment->scheduled_at?->toIso8601String(),
                'delivered_at' => $order->shipment->delivered_at?->toIso8601String(),
                'driver_name' => $order->shipment->driver_name,
                'driver_phone' => $order->shipment->driver_phone,
            ] : null,
        ];
    }

    private function breadcrumbs(string $label): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => $label, 'href' => url()->current()],
        ];
    }

    private function orderStatusOptions(): array
    {
        return collect(['', 'menunggu_pembayaran', 'dibayar', 'perlu_review_admin', 'diproses', 'dikirim', 'selesai', 'dibatalkan'])
            ->map(fn (string $status) => ['value' => $status, 'label' => $status === '' ? 'Semua status' : str_replace('_', ' ', $status)])
            ->values()
            ->all();
    }

    private function paymentStatusOptions(): array
    {
        return collect(['', 'pending', 'success', 'failed', 'expired', 'cancelled'])
            ->map(fn (string $status) => ['value' => $status, 'label' => $status === '' ? 'Semua status' : $status])
            ->values()
            ->all();
    }

    private function shipmentStatusOptions(): array
    {
        return collect(['', 'belum_dijadwalkan', 'dijadwalkan', 'dalam_pengiriman', 'terkirim', 'gagal_dikirim'])
            ->map(fn (string $status) => ['value' => $status, 'label' => $status === '' ? 'Semua status' : str_replace('_', ' ', $status)])
            ->values()
            ->all();
    }
}
