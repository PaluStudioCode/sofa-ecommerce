<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
            ],
            'role' => $request->user()->role,
            'summary' => $this->summary(),
            'recentOrders' => $this->recentOrders(),
        ]);
    }

    private function summary(): array
    {
        return [
            'incoming_orders' => Order::query()->whereDate('created_at', today())->count(),
            'pending_payments' => Payment::query()->where('status', 'pending')->count(),
            'processing_orders' => Order::query()->where('order_status', 'diproses')->count(),
            'active_shipments' => Shipment::query()->whereIn('status', ['dijadwalkan', 'dalam_pengiriman'])->count(),
        ];
    }

    private function recentOrders(): array
    {
        return Order::query()
            ->with(['shipment:id,order_id,status'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'total_amount' => (float) $order->total_amount,
                'order_status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'shipment_status' => $order->shipment?->status ?? 'belum_dijadwalkan',
            ])
            ->all();
    }

}
