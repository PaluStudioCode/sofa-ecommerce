<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\VoucherUsage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BusinessReportService
{
    public function period(array $filters): array
    {
        return [
            'date_from' => $filters['date_from'] ?? now()->startOfMonth()->toDateString(),
            'date_to' => $filters['date_to'] ?? now()->toDateString(),
        ];
    }

    public function salesSummary(array $period): array
    {
        $orders = $this->successfulOrders($period);

        return [
            'total_sales' => (float) (clone $orders)->sum('total_amount'),
            'orders_count' => (clone $orders)->count(),
            'products_sold' => (int) $this->soldItems($period)->sum('order_items.quantity'),
            'voucher_discount' => (float) (clone $orders)->sum('discount_amount'),
            'shipping_cost' => (float) (clone $orders)->sum('shipping_cost'),
        ];
    }

    public function salesRows(array $period): array
    {
        return $this->successfulOrders($period)
            ->with(['user:id,name,email', 'shipment:id,order_id,status'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->user?->email,
                'total_amount' => (float) $order->total_amount,
                'discount_amount' => (float) $order->discount_amount,
                'shipping_cost' => (float) $order->shipping_cost,
                'order_status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'shipment_status' => $order->shipment?->status ?? 'belum_dijadwalkan',
                'created_at' => $order->created_at?->toIso8601String(),
            ])
            ->all();
    }

    public function productRows(array $period): array
    {
        return $this->soldItems($period)
            ->select([
                'order_items.product_name',
                'order_items.variant_name',
                'order_items.variant_sku',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.subtotal) as gross_sales'),
            ])
            ->groupBy('order_items.product_name', 'order_items.variant_name', 'order_items.variant_sku')
            ->orderByDesc('quantity_sold')
            ->limit(30)
            ->get()
            ->map(fn ($row) => [
                'id' => md5($row->product_name.'|'.$row->variant_name.'|'.$row->variant_sku),
                'product_name' => $row->product_name,
                'variant_name' => $row->variant_name,
                'variant_sku' => $row->variant_sku,
                'quantity_sold' => (int) $row->quantity_sold,
                'gross_sales' => (float) $row->gross_sales,
            ])
            ->all();
    }

    public function voucherRows(array $period): array
    {
        return VoucherUsage::query()
            ->join('orders', 'orders.id', '=', 'voucher_usages.order_id')
            ->join('vouchers', 'vouchers.id', '=', 'voucher_usages.voucher_id')
            ->where('orders.payment_status', 'success')
            ->where('orders.order_status', '!=', 'menunggu_pembayaran')
            ->whereDate('orders.created_at', '>=', $period['date_from'])
            ->whereDate('orders.created_at', '<=', $period['date_to'])
            ->select([
                'vouchers.id',
                'vouchers.code',
                'vouchers.name',
                DB::raw('COUNT(voucher_usages.id) as usage_count'),
                DB::raw('SUM(voucher_usages.discount_amount) as discount_total'),
            ])
            ->groupBy('vouchers.id', 'vouchers.code', 'vouchers.name')
            ->orderByDesc('usage_count')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'usage_count' => (int) $row->usage_count,
                'discount_total' => (float) $row->discount_total,
            ])
            ->all();
    }

    public function shippingRows(array $period): array
    {
        return $this->successfulOrders($period)
            ->leftJoin('stores', 'stores.id', '=', 'orders.store_id')
            ->select([
                DB::raw("COALESCE(stores.name, 'Tanpa toko') as area_name"),
                DB::raw('COUNT(orders.id) as orders_count'),
                DB::raw('SUM(orders.shipping_cost) as shipping_total'),
            ])
            ->groupBy('area_name')
            ->orderByDesc('shipping_total')
            ->get()
            ->map(fn ($row) => [
                'id' => md5($row->area_name),
                'area_name' => $row->area_name,
                'orders_count' => (int) $row->orders_count,
                'shipping_total' => (float) $row->shipping_total,
            ])
            ->all();
    }

    public function successfulOrders(array $period): Builder
    {
        return Order::query()
            ->where('orders.payment_status', 'success')
            ->where('orders.order_status', '!=', 'menunggu_pembayaran')
            ->whereDate('orders.created_at', '>=', $period['date_from'])
            ->whereDate('orders.created_at', '<=', $period['date_to']);
    }

    private function soldItems(array $period): Builder
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'success')
            ->where('orders.order_status', '!=', 'menunggu_pembayaran')
            ->whereDate('orders.created_at', '>=', $period['date_from'])
            ->whereDate('orders.created_at', '<=', $period['date_to']);
    }
}
