<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Notifications\WhatsAppNotificationService;
use App\Services\Shipping\ShipmentStatusTransitionService;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ShipmentController extends Controller
{
    public function __construct(
        private readonly ShipmentStatusTransitionService $transitions,
        private readonly WhatsAppNotificationService $notifications,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['', ...ShipmentStatusTransitionService::STATUSES])],
        ]);

        $orders = Order::query()
            ->with(['user:id,name,email', 'shipment'])
            ->where('payment_status', 'success')
            ->whereIn('order_status', ['dibayar', 'diproses', 'dikirim', 'selesai'])
            ->when($filters['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('order_number', 'like', "%{$keyword}%")
                        ->orWhere('customer_name', 'like', "%{$keyword}%")
                        ->orWhereHas('user', fn ($query) => $query->where('email', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['status'] ?? null, function ($query, string $status) {
                if ($status === 'belum_dijadwalkan') {
                    $query->where(function ($query) {
                        $query->whereDoesntHave('shipment')
                            ->orWhereHas('shipment', fn ($query) => $query->where('status', 'belum_dijadwalkan'));
                    });

                    return;
                }

                $query->whereHas('shipment', fn ($query) => $query->where('status', $status));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Order $order) => $this->payload($order));

        return Inertia::render('Admin/Shipments/Index', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Pengiriman Internal', 'href' => route('admin.shipments.index')],
            ],
            'orders' => $orders,
            'filters' => [
                'keyword' => $filters['keyword'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'statusOptions' => $this->statusOptions(true),
            'formStatusOptions' => $this->statusOptions(false),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        if ($order->payment_status !== 'success' || in_array($order->order_status, ['menunggu_pembayaran', 'dibatalkan'], true)) {
            throw ValidationException::withMessages([
                'order' => 'Shipment hanya dapat dibuat untuk order yang sudah dibayar dan belum dibatalkan.',
            ]);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(ShipmentStatusTransitionService::STATUSES)],
            'scheduled_at' => ['nullable', 'date', 'required_if:status,dijadwalkan,dalam_pengiriman,terkirim'],
            'delivered_at' => ['nullable', 'date', 'after_or_equal:scheduled_at', 'required_if:status,terkirim'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'vehicle_note' => ['nullable', 'string', 'max:255'],
            'shipping_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $shipment = $order->shipment;
        $this->transitions->assertCanTransition($shipment, $data['status']);

        $shipment
            ? $shipment->update($data)
            : $order->shipment()->create($data);

        $updatedOrder = $order->fresh('shipment');

        $this->syncOrderStatus($updatedOrder, $data['status']);
        $this->notifications->sendForShipmentStatus($updatedOrder->fresh('shipment'), $data['status']);

        return back()->with('success', 'Shipment diperbarui.');
    }

    private function syncOrderStatus(Order $order, string $shipmentStatus): void
    {
        $nextStatus = match ($shipmentStatus) {
            'belum_dijadwalkan', 'dijadwalkan', 'gagal_dikirim' => 'diproses',
            'dalam_pengiriman' => 'dikirim',
            'terkirim' => 'selesai',
            default => $order->order_status,
        };

        if ($nextStatus !== $order->order_status) {
            $order->update(['order_status' => $nextStatus]);
        }
    }

    private function payload(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->user?->email,
            'shipping_address' => $order->shipping_address,
            'total_amount' => (float) $order->total_amount,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'shipment' => $order->shipment ? [
                'status' => $order->shipment->status,
                'scheduled_at' => $order->shipment->scheduled_at?->format('Y-m-d\TH:i'),
                'delivered_at' => $order->shipment->delivered_at?->format('Y-m-d\TH:i'),
                'driver_name' => $order->shipment->driver_name,
                'driver_phone' => $order->shipment->driver_phone,
                'vehicle_note' => $order->shipment->vehicle_note,
                'shipping_note' => $order->shipment->shipping_note,
            ] : null,
            'shipment_status' => $order->shipment?->status ?? 'belum_dijadwalkan',
            'allowed_statuses' => $this->transitions->allowedStatuses($order->shipment),
        ];
    }

    private function statusOptions(bool $withAll): array
    {
        $options = collect([
            ['value' => 'belum_dijadwalkan', 'label' => 'Belum dijadwalkan'],
            ['value' => 'dijadwalkan', 'label' => 'Dijadwalkan'],
            ['value' => 'dalam_pengiriman', 'label' => 'Dalam pengiriman'],
            ['value' => 'terkirim', 'label' => 'Terkirim'],
            ['value' => 'gagal_dikirim', 'label' => 'Gagal dikirim'],
        ]);

        return $withAll
            ? $options->prepend(['value' => '', 'label' => 'Semua status'])->values()->all()
            : $options->values()->all();
    }
}
