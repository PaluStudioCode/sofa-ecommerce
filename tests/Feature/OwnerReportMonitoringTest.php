<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OwnerReportMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_dashboard_and_sales_report_show_business_summary_for_period(): void
    {
        $owner = User::factory()->owner()->create();
        $area = Store::factory()->create(['name' => 'Jakarta']);
        $successful = $this->successfulOrder([
            'store_id' => $area->id,
            'subtotal_amount' => 2000000,
            'discount_amount' => 200000,
            'shipping_cost' => 150000,
            'total_amount' => 1950000,
            'created_at' => '2026-05-10 10:00:00',
        ], 2, 1000000);
        $this->successfulOrder([
            'subtotal_amount' => 1000000,
            'discount_amount' => 0,
            'shipping_cost' => 100000,
            'total_amount' => 1100000,
            'created_at' => '2026-04-01 10:00:00',
        ], 1, 1000000);
        Order::factory()->create([
            'payment_status' => 'pending',
            'order_status' => 'menunggu_pembayaran',
            'total_amount' => 999999,
            'created_at' => '2026-05-11 10:00:00',
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('role', 'owner')
                ->has('navigationGroups.1.items', 4)
            );

        $this->actingAs($owner)
            ->get(route('owner.reports.sales', ['date_from' => '2026-05-01', 'date_to' => '2026-05-31']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Reports/Sales')
                ->where('summary.total_sales', 1950000)
                ->where('summary.orders_count', 1)
                ->where('summary.products_sold', 2)
                ->where('summary.voucher_discount', 200000)
                ->where('summary.shipping_cost', 150000)
                ->where('rows.0.id', $successful->id)
            );
    }

    public function test_owner_reports_include_products_vouchers_and_shipping_costs(): void
    {
        $owner = User::factory()->owner()->create();
        $customer = User::factory()->create();
        $voucher = Voucher::factory()->create(['code' => 'OWNER10', 'name' => 'Owner Diskon']);
        $area = Store::factory()->create(['name' => 'Bekasi']);
        $order = $this->successfulOrder([
            'user_id' => $customer->id,
            'voucher_id' => $voucher->id,
            'store_id' => $area->id,
            'subtotal_amount' => 3000000,
            'discount_amount' => 300000,
            'shipping_cost' => 200000,
            'total_amount' => 2900000,
            'created_at' => '2026-05-12 10:00:00',
        ], 3, 1000000, 'Sofa Laporan');

        VoucherUsage::factory()->create([
            'voucher_id' => $voucher->id,
            'user_id' => $customer->id,
            'order_id' => $order->id,
            'discount_amount' => 300000,
            'used_at' => '2026-05-12 10:00:00',
        ]);

        $period = ['date_from' => '2026-05-01', 'date_to' => '2026-05-31'];

        $this->actingAs($owner)
            ->get(route('owner.reports.products', $period))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Reports/Products')
                ->where('rows.0.product_name', 'Sofa Laporan')
                ->where('rows.0.quantity_sold', 3)
                ->where('rows.0.gross_sales', 3000000)
            );

        $this->actingAs($owner)
            ->get(route('owner.reports.vouchers', $period))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Reports/Vouchers')
                ->where('rows.0.code', 'OWNER10')
                ->where('rows.0.usage_count', 1)
                ->where('rows.0.discount_total', 300000)
            );

        $this->actingAs($owner)
            ->get(route('owner.reports.shipping', $period))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Reports/Shipping')
                ->where('rows.0.area_name', 'Bekasi')
                ->where('rows.0.orders_count', 1)
                ->where('rows.0.shipping_total', 200000)
            );
    }

    public function test_owner_can_monitor_orders_payments_and_shipments_read_only(): void
    {
        $owner = User::factory()->owner()->create();
        $order = $this->successfulOrder([
            'order_number' => 'ORD-OWNER-001',
            'customer_name' => 'Owner Customer',
            'customer_phone' => '0811111111',
            'shipping_address' => 'Jl. Owner No. 1',
            'shipping_latitude' => -6.2,
            'shipping_longitude' => 106.816666,
        ], 1, 1000000);
        $payment = Payment::factory()->for($order)->success()->create([
            'midtrans_order_id' => 'MT-OWNER-001',
            'gross_amount' => $order->total_amount,
            'raw_response' => ['signature_key' => 'secret'],
        ]);
        Shipment::factory()->for($order)->create([
            'status' => 'dijadwalkan',
            'driver_name' => 'Kurir Owner',
        ]);

        $this->actingAs($owner)
            ->get(route('owner.monitoring.orders', ['keyword' => 'OWNER']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Monitoring/Orders')
                ->where('orders.data.0.order_number', 'ORD-OWNER-001')
            );

        $this->actingAs($owner)
            ->get(route('owner.monitoring.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Monitoring/OrderShow')
                ->where('order.customer_phone', '0811111111')
                ->where('order.shipping_latitude', -6.2)
                ->where('order.payments.0.midtrans_order_id', 'MT-OWNER-001')
                ->missing('order.payments.0.raw_response')
                ->missing('order.payments.0.raw_response_preview')
            );

        $this->actingAs($owner)
            ->get(route('owner.monitoring.payments', ['status' => 'success']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Monitoring/Payments')
                ->where('payments.data.0.id', $payment->id)
                ->missing('payments.data.0.raw_response')
            );

        $this->actingAs($owner)
            ->get(route('owner.monitoring.shipments', ['status' => 'dijadwalkan']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Monitoring/Shipments')
                ->where('shipments.data.0.driver_name', 'Kurir Owner')
                ->where('shipments.data.0.order.order_number', 'ORD-OWNER-001')
            );
    }

    public function test_reports_are_view_reports_permission_and_monitoring_is_owner_only(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        $customer = User::factory()->create();
        $order = Order::factory()->paid()->create();

        $this->actingAs($admin)
            ->get(route('owner.reports.sales'))
            ->assertOk();

        $this->actingAs($customer)
            ->get(route('owner.reports.sales'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('owner.monitoring.orders'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('owner.monitoring.orders'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('admin.orders.update', $order), ['order_status' => 'diproses'])
            ->assertForbidden();
    }

    private function successfulOrder(array $attributes = [], int $quantity = 1, int $price = 1000000, string $productName = 'Sofa Report'): Order
    {
        $product = Product::factory()->create(['name' => $productName]);
        $variant = ProductVariant::factory()->for($product)->create([
            'variant_name' => 'Standar',
            'price' => $price,
        ]);

        $order = Order::factory()->create([
            'order_status' => 'dibayar',
            'payment_status' => 'success',
            'subtotal_amount' => $price * $quantity,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => $price * $quantity,
            ...$attributes,
        ]);

        OrderItem::factory()->for($order)->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $productName,
            'variant_name' => $variant->variant_name,
            'variant_sku' => $variant->sku,
            'product_price' => $price,
            'quantity' => $quantity,
            'subtotal' => $price * $quantity,
        ]);

        return $order;
    }
}
