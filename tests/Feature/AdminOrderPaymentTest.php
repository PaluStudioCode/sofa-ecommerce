<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminOrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_orders_and_view_sensitive_order_detail_with_limited_raw_payment_response(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['email' => 'budi@example.test']);
        $order = Order::factory()->for($customer)->paid()->create([
            'order_number' => 'ORD-ADMIN-001',
            'customer_name' => 'Budi Sofa',
            'customer_phone' => '08123456789',
            'shipping_latitude' => -6.2,
            'shipping_longitude' => 106.816666,
        ]);
        OrderItem::factory()->for($order)->create(['product_name' => 'Sofa L Admin']);
        Shipment::factory()->for($order)->create(['status' => 'dijadwalkan']);
        Payment::factory()->for($order)->success()->create([
            'attempt_number' => 1,
            'midtrans_order_id' => 'MIDTRANS-ADMIN-001',
            'gross_amount' => $order->total_amount,
            'raw_response' => [
                'order_id' => 'MIDTRANS-ADMIN-001',
                'transaction_status' => 'settlement',
                'payment_type' => 'bank_transfer',
                'gross_amount' => (string) $order->total_amount,
                'signature_key' => 'secret-signature',
                'va_numbers' => [['bank' => 'bca', 'va_number' => '123']],
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', [
                'keyword' => 'Budi',
                'order_status' => 'dibayar',
                'payment_status' => 'success',
                'shipment_status' => 'dijadwalkan',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.order_number', 'ORD-ADMIN-001')
                ->where('filters.payment_status', 'success')
            );

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Show')
                ->where('order.customer_phone', '08123456789')
                ->where('order.shipping_latitude', -6.2)
                ->where('order.payments.0.raw_response_preview.order_id', 'MIDTRANS-ADMIN-001')
                ->missing('order.payments.0.raw_response_preview.signature_key')
                ->missing('order.payments.0.raw_response_preview.va_numbers')
            );
    }

    public function test_admin_order_status_transitions_are_validated_and_cancel_releases_reserved_stock_before_success(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create([
            'stock' => 5,
            'reserved_stock' => 2,
            'status' => 'aktif',
        ]);
        $pending = Order::factory()->create([
            'order_status' => 'menunggu_pembayaran',
            'payment_status' => 'pending',
        ]);
        OrderItem::factory()->for($pending)->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'product_name' => $product->name,
            'variant_name' => $variant->variant_name,
            'product_price' => $variant->price,
            'subtotal' => $variant->price * 2,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $pending), ['order_status' => 'diproses'])
            ->assertSessionHasErrors('order_status');

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $pending), ['order_status' => 'dibatalkan'])
            ->assertRedirect();

        $this->assertSame('dibatalkan', $pending->fresh()->order_status);
        $this->assertSame(0, $variant->fresh()->reserved_stock);

        $paid = Order::factory()->paid()->create();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $paid), ['order_status' => 'diproses'])
            ->assertRedirect();

        $this->assertSame('diproses', $paid->fresh()->order_status);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $paid), ['order_status' => 'dikirim'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $paid), ['order_status' => 'selesai'])
            ->assertRedirect();

        $this->assertSame('selesai', $paid->fresh()->order_status);
    }

    public function test_review_order_can_be_processed_or_cancelled_by_admin_only(): void
    {
        $admin = User::factory()->admin()->create();
        $review = Order::factory()->create([
            'order_status' => 'perlu_review_admin',
            'payment_status' => 'success',
        ]);
        $secondReview = Order::factory()->create([
            'order_status' => 'perlu_review_admin',
            'payment_status' => 'success',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $review), ['order_status' => 'diproses'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $secondReview), ['order_status' => 'dibatalkan'])
            ->assertRedirect();

        $this->assertSame('diproses', $review->fresh()->order_status);
        $this->assertSame('dibatalkan', $secondReview->fresh()->order_status);
        $this->assertSame('success', $secondReview->fresh()->payment_status);
    }

    public function test_admin_can_filter_and_view_payment_detail_without_mutation_route(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->paid()->create([
            'order_number' => 'ORD-PAY-001',
            'customer_name' => 'Citra Sofa',
        ]);
        $payment = Payment::factory()->for($order)->success()->create([
            'midtrans_order_id' => 'MIDTRANS-PAY-001',
            'gross_amount' => $order->total_amount,
            'raw_response' => [
                'order_id' => 'MIDTRANS-PAY-001',
                'transaction_status' => 'settlement',
                'payment_type' => 'qris',
                'signature_key' => 'secret-signature',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payments.index', ['keyword' => 'Citra', 'status' => 'success']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Payments/Index')
                ->has('payments.data', 1)
                ->where('payments.data.0.midtrans_order_id', 'MIDTRANS-PAY-001')
            );

        $this->actingAs($admin)
            ->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Payments/Show')
                ->where('payment.raw_response_preview.order_id', 'MIDTRANS-PAY-001')
                ->where('payment.raw_response_preview.payment_type', 'qris')
                ->missing('payment.raw_response_preview.signature_key')
            );

        $this->actingAs($admin)
            ->put("/dashboard/payments/{$payment->id}", ['status' => 'success'])
            ->assertMethodNotAllowed();
    }

    public function test_customer_order_detail_does_not_receive_midtrans_raw_response(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->for($customer)->paid()->create();
        Payment::factory()->for($order)->success()->create([
            'raw_response' => [
                'order_id' => 'MIDTRANS-CUSTOMER-001',
                'signature_key' => 'secret-signature',
            ],
        ]);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('order.payments.0.raw_response')
                ->missing('order.payments.0.raw_response_preview')
            );
    }

    public function test_order_and_payment_management_are_admin_only(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.orders.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.payments.index'))
            ->assertForbidden();
    }
}
