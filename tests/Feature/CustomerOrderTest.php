<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_and_customer_only_sees_their_order_history(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        [, $ownOrder] = $this->orderFor($customer, orderNumber: 'ORD-CUSTOMER-001');
        $this->orderFor($otherCustomer, orderNumber: 'ORD-OTHER-001');

        $this->get(route('orders.index'))->assertRedirect('/login');

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Orders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $ownOrder->id)
                ->where('orders.data.0.order_number', 'ORD-CUSTOMER-001')
                ->where('orders.data.0.payment_status', 'pending')
                ->where('orders.data.0.shipment_status', 'belum_dijadwalkan')
                ->where('orders.data.0.shipment_label', 'Belum dijadwalkan')
                ->where('orders.data.0.shipment_summary', 'Pengiriman belum dijadwalkan.')
                ->missing('orders.data.0.latest_payment.snap_token')
                ->missing('orders.data.0.shipping_latitude')
            );

        $this->actingAs($customer)
            ->get(route('orders.show', $otherCustomer->orders()->firstOrFail()))
            ->assertNotFound();
    }

    public function test_customer_can_view_order_detail_with_timeline_and_without_sensitive_raw_data_or_coordinates(): void
    {
        $customer = User::factory()->create(['name' => 'Customer Sofa']);
        $voucher = Voucher::factory()->create(['code' => 'SOFAHEMAT', 'status' => 'aktif']);
        [$variant, $order] = $this->orderFor($customer, voucherId: $voucher->id, orderStatus: 'dibayar', paymentStatus: 'success');
        $payment = Payment::factory()->success()->for($order)->create([
            'midtrans_order_id' => $order->order_number.'-ATTEMPT-1',
            'gross_amount' => $order->total_amount,
            'raw_response' => ['signature_key' => 'secret-signature'],
        ]);
        Shipment::factory()->for($order)->create([
            'status' => 'dijadwalkan',
            'scheduled_at' => now()->addDay(),
            'driver_name' => 'Admin Delivery',
        ]);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Orders/Show')
                ->where('order.order_number', $order->order_number)
                ->where('order.customer_name', 'Customer Sofa')
                ->where('order.items.0.product_name', 'Sofa Riwayat')
                ->where('order.voucher.code', 'SOFAHEMAT')
                ->where('order.payment.status', 'success')
                ->where('order.payment.midtrans_order_id', $payment->midtrans_order_id)
                ->where('order.shipment_status', 'dijadwalkan')
                ->where('order.shipment_label', 'Dijadwalkan')
                ->where('order.timeline.3.label', 'Pengiriman belum dijadwalkan')
                ->where('order.timeline.3.state', 'completed')
                ->where('order.timeline.4.label', 'Pengiriman dijadwalkan')
                ->where('order.timeline.4.state', 'current')
                ->where('order.timeline.1.label', 'Pembayaran berhasil')
                ->where('order.timeline.1.state', 'completed')
                ->where('order.can_open_payment', false)
                ->where('order.can_create_payment_attempt', false)
                ->missing('order.shipping_latitude')
                ->missing('order.shipping_longitude')
                ->missing('order.payment.raw_response')
                ->missing('order.payments.0.raw_response')
                ->where('midtrans.clientKey', 'fake-client-key')
            );

        $this->assertSame(8, $variant->fresh()->stock);
    }

    public function test_pending_order_detail_can_reopen_payment_but_cannot_create_new_attempt(): void
    {
        $customer = User::factory()->create();
        [, $order] = $this->orderFor($customer);
        $payment = Payment::factory()->for($order)->create([
            'midtrans_order_id' => $order->order_number.'-ATTEMPT-1',
            'gross_amount' => $order->total_amount,
            'snap_token' => 'fake-snap-token-'.$order->order_number,
            'expired_at' => now()->addHour(),
        ]);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.payment.status', 'pending')
                ->where('order.payment.snap_token', $payment->snap_token)
                ->where('order.can_open_payment', true)
                ->where('order.can_create_payment_attempt', false)
                ->where('order.timeline.1.state', 'current')
            );
    }

    public function test_failed_or_expired_order_detail_can_create_new_payment_attempt_when_no_pending_or_success_exists(): void
    {
        $customer = User::factory()->create();
        [$variant, $order] = $this->orderFor($customer, reserved: 0, paymentStatus: 'expired');
        Payment::factory()->for($order)->create([
            'attempt_number' => 1,
            'midtrans_order_id' => $order->order_number.'-ATTEMPT-1',
            'status' => 'expired',
            'transaction_status' => 'expire',
            'gross_amount' => $order->total_amount,
            'snap_token' => 'expired-token',
            'expired_at' => now()->subHour(),
        ]);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.can_open_payment', false)
                ->where('order.can_create_payment_attempt', true)
                ->where('order.timeline.1.state', 'blocked')
            );

        $this->actingAs($customer)
            ->post(route('payments.store', $order), ['return_to' => 'order'])
            ->assertRedirect(route('orders.show', ['order' => $order->id]));

        $newPayment = $order->fresh()->payments()->latest('attempt_number')->firstOrFail();

        $this->assertSame(2, $newPayment->attempt_number);
        $this->assertSame('pending', $newPayment->status);
        $this->assertSame($order->order_number.'-ATTEMPT-2', $newPayment->midtrans_order_id);
        $this->assertSame(2, $variant->fresh()->reserved_stock);
    }

    public function test_review_and_cancelled_orders_show_the_correct_customer_timeline(): void
    {
        $customer = User::factory()->create();
        [, $reviewOrder] = $this->orderFor($customer, orderNumber: 'ORD-REVIEW-001', orderStatus: 'perlu_review_admin', paymentStatus: 'success');
        Payment::factory()->success()->for($reviewOrder)->create([
            'midtrans_order_id' => $reviewOrder->order_number.'-ATTEMPT-1',
            'gross_amount' => $reviewOrder->total_amount,
        ]);

        [, $cancelledOrder] = $this->orderFor($customer, orderNumber: 'ORD-CANCEL-001', orderStatus: 'dibatalkan', paymentStatus: 'cancelled');
        Payment::factory()->for($cancelledOrder)->create([
            'midtrans_order_id' => $cancelledOrder->order_number.'-ATTEMPT-1',
            'status' => 'cancelled',
            'transaction_status' => 'cancel',
            'gross_amount' => $cancelledOrder->total_amount,
        ]);

        $this->actingAs($customer)
            ->get(route('orders.show', $reviewOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.order_status', 'perlu_review_admin')
                ->where('order.timeline.1.label', 'Perlu review admin')
                ->where('order.timeline.1.state', 'current')
                ->where('order.can_create_payment_attempt', false)
            );

        $this->actingAs($customer)
            ->get(route('orders.show', $cancelledOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.order_status', 'dibatalkan')
                ->has('order.timeline', 2)
                ->where('order.timeline.1.label', 'Pesanan dibatalkan')
                ->where('order.can_create_payment_attempt', false)
            );
    }

    private function orderFor(
        User $customer,
        ?string $orderNumber = null,
        int $stock = 8,
        int $reserved = 2,
        int $quantity = 2,
        string $orderStatus = 'menunggu_pembayaran',
        string $paymentStatus = 'pending',
        ?int $voucherId = null
    ): array {
        $product = Product::factory()->create([
            'name' => 'Sofa Riwayat',
            'status' => 'aktif',
        ]);
        $variant = ProductVariant::factory()->for($product)->create([
            'variant_name' => 'Premium',
            'sku' => fake()->unique()->bothify('HST-###'),
            'price' => 2500000,
            'stock' => $stock,
            'reserved_stock' => $reserved,
            'status' => 'aktif',
        ]);
        $subtotal = (float) $variant->price * $quantity;

        $order = Order::factory()->for($customer)->create([
            'order_number' => $orderNumber ?? 'ORD-'.fake()->unique()->numerify('########'),
            'voucher_id' => $voucherId,
            'customer_name' => $customer->name,
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Riwayat Sofa No. 9, Jakarta',
            'shipping_city' => 'Jakarta',
            'shipping_district' => 'Kebayoran',
            'shipping_postal_code' => '12110',
            'shipping_note' => 'Rumah pagar hitam',
            'subtotal_amount' => $subtotal,
            'discount_amount' => $voucherId ? 100000 : 0,
            'shipping_cost' => 100000,
            'total_amount' => $subtotal - ($voucherId ? 100000 : 0) + 100000,
            'order_status' => $orderStatus,
            'payment_status' => $paymentStatus,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->variant_name,
            'variant_sku' => $variant->sku,
            'variant_size' => $variant->size,
            'variant_material' => $variant->material,
            'variant_color' => $variant->color,
            'product_price' => $variant->price,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
        ]);

        return [$variant, $order];
    }
}
