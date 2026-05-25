<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\OrderStatusTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    public function __construct(
        private readonly OrderStatusTransitionService $transitions,
    ) {}

    public function update(Request $request, Order $order): RedirectResponse
    {
        if ($order->payment_status !== 'success' || $order->order_status === 'menunggu_pembayaran') {
            throw ValidationException::withMessages([
                'order' => 'Pengiriman hanya dapat dibuat untuk order yang sudah dibayar.',
            ]);
        }

        $data = $request->validate([
            'order_status' => ['required', Rule::in(['diproses', ...OrderStatusTransitionService::ADMIN_ACTION_STATUSES])],
            'scheduled_at' => ['nullable', 'date', 'required_if:order_status,dalam_perjalanan,barang_diterima'],
            'delivered_at' => ['nullable', 'date', 'after_or_equal:scheduled_at', 'required_if:order_status,barang_diterima'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'vehicle_note' => ['nullable', 'string', 'max:255'],
            'shipping_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $order->delivery()->updateOrCreate([], [
            'delivery_scheduled_at' => $data['scheduled_at'] ?? null,
            'delivery_delivered_at' => $data['delivered_at'] ?? null,
            'driver_name' => $data['driver_name'] ?? null,
            'driver_phone' => $data['driver_phone'] ?? null,
            'vehicle_note' => $data['vehicle_note'] ?? null,
            'delivery_note' => $data['shipping_note'] ?? null,
        ]);

        if ($order->order_status !== $data['order_status']) {
            $this->transitions->updateByAdmin($order, $data['order_status']);
        }

        return back()->with('success', 'Pengiriman diperbarui.');
    }
}
