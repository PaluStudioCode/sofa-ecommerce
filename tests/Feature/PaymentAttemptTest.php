<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_initial_midtrans_payment_attempt_and_exposes_safe_snap_props(): void
    {
        $customer = User::factory()->create(['name' => 'Customer Sofa']);
        [$product, $variant] = $this->activeProductAndVariant(price: 3000000, stock: 5);
        CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
        Store::factory()->create([
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'radius_km' => 10,
            'shipping_cost' => 100000,
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->post(route('checkout.location'), $this->locationPayload())
            ->assertRedirect(route('checkout.index'));

        $response = $this->actingAs($customer)
            ->post(route('checkout.store'), [
                'customer_phone' => '081234567890',
                'shipping_note' => 'Blok A nomor 10',
            ]);

        $order = Order::with('payments')->firstOrFail();
        $payment = $order->payments->first();

        $response->assertRedirect(route('orders.show', ['order' => $order->id, 'new_order' => 1]));

        $this->assertSame('pending', $payment->status);
        $this->assertSame(1, $payment->attempt_number);
        $this->assertSame($order->order_number.'-ATTEMPT-1', $payment->midtrans_order_id);
        $this->assertSame((float) $order->total_amount, (float) $payment->gross_amount);
        $this->assertSame('fake-snap-token-'.$payment->midtrans_order_id, $payment->snap_token);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Orders/Show')
                ->where('order.order_number', $order->order_number)
                ->where('order.payment.status', 'pending')
                ->where('order.payment.snap_token', $payment->snap_token)
                ->where('order.can_create_payment_attempt', false)
                ->where('paymentGateway.clientKey', 'fake-client-key')
                ->missing('paymentGateway.serverKey')
                ->missing('order.payment.midtrans_order_id')
            );
    }

    public function test_customer_cannot_create_new_attempt_while_pending_or_for_another_customer_order(): void
    {
        [$customer, $order] = $this->payableOrder();
        $otherCustomer = User::factory()->create();

        $this->actingAs($customer)
            ->post(route('payments.store', $order))
            ->assertSessionHasErrors('order');

        $this->actingAs($otherCustomer)
            ->post(route('payments.store', $order))
            ->assertNotFound();
    }

    public function test_expired_callback_releases_reserved_stock_and_allows_new_attempt_with_new_reservation(): void
    {
        [$customer, $order, $variant, $payment] = $this->payableOrder(stock: 5, reserved: 2, quantity: 2);

        $this->postJson(route('midtrans.callback'), $this->callbackPayload($payment, 'expire'))
            ->assertOk()
            ->assertJsonPath('status', 'expired');

        $this->assertSame('expired', $payment->fresh()->status);
        $this->assertSame('expired', $order->fresh()->payment_status);
        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertSame(0, $variant->fresh()->reserved_stock);

        $this->postJson(route('midtrans.callback'), $this->callbackPayload($payment, 'pending'))
            ->assertOk()
            ->assertJsonPath('status', 'expired');

        $this->assertSame('expired', $payment->fresh()->status);
        $this->assertSame('expired', $order->fresh()->payment_status);
        $this->assertSame(0, $variant->fresh()->reserved_stock);

        $this->actingAs($customer)
            ->post(route('payments.store', $order))
            ->assertRedirect(route('orders.show', ['order' => $order->id, 'payment_attempt' => 1]));

        $latestPayment = $order->fresh()->payments()->latest('attempt_number')->firstOrFail();

        $this->assertSame(2, $latestPayment->attempt_number);
        $this->assertSame('pending', $latestPayment->status);
        $this->assertSame($order->order_number.'-ATTEMPT-2', $latestPayment->midtrans_order_id);
        $this->assertSame(2, $variant->fresh()->reserved_stock);
    }

    public function test_success_callback_converts_reserved_stock_once_for_duplicate_callbacks(): void
    {
        [, $order, $variant, $payment] = $this->payableOrder(stock: 5, reserved: 2, quantity: 2);
        $payload = $this->callbackPayload($payment, 'settlement');

        $this->postJson(route('midtrans.callback'), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame('success', $order->fresh()->payment_status);
        $this->assertSame('dibayar', $order->fresh()->order_status);
        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertSame(0, $variant->fresh()->reserved_stock);

        $this->postJson(route('midtrans.callback'), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->postJson(route('midtrans.callback'), $this->callbackPayload($payment, 'expire'))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertSame(0, $variant->fresh()->reserved_stock);
    }

    public function test_invalid_signature_and_gross_amount_mismatch_are_rejected_without_state_changes(): void
    {
        [, $order, $variant, $payment] = $this->payableOrder(stock: 5, reserved: 2, quantity: 2);

        $this->postJson(route('midtrans.callback'), $this->callbackPayload($payment, 'settlement', [
            'signature_valid' => false,
        ]))->assertForbidden();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(2, $variant->fresh()->reserved_stock);

        $this->postJson(route('midtrans.callback'), $this->callbackPayload($payment, 'settlement', [
            'gross_amount' => '999.00',
        ]))->assertUnprocessable();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(2, $variant->fresh()->reserved_stock);
    }

    public function test_success_callback_with_invalid_reserved_stock_marks_order_for_admin_review(): void
    {
        [, $order, $variant, $payment] = $this->payableOrder(stock: 5, reserved: 1, quantity: 2);

        $this->postJson(route('midtrans.callback'), $this->callbackPayload($payment, 'settlement'))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame('success', $order->fresh()->payment_status);
        $this->assertSame('perlu_review_admin', $order->fresh()->order_status);
        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertSame(1, $variant->fresh()->reserved_stock);
    }

    public function test_callback_maps_midtrans_pending_failure_and_cancel_statuses(): void
    {
        foreach ([
            'pending' => 'pending',
            'deny' => 'failed',
            'failure' => 'failed',
            'cancel' => 'cancelled',
        ] as $midtransStatus => $expectedStatus) {
            [, $order, $variant, $payment] = $this->payableOrder(stock: 5, reserved: 2, quantity: 2);

            $this->postJson(route('midtrans.callback'), $this->callbackPayload($payment, $midtransStatus))
                ->assertOk()
                ->assertJsonPath('status', $expectedStatus);

            $this->assertSame($expectedStatus, $payment->fresh()->status);
            $this->assertSame($expectedStatus, $order->fresh()->payment_status);
            $this->assertSame($expectedStatus === 'pending' ? 2 : 0, $variant->fresh()->reserved_stock);
        }
    }

    private function locationPayload(): array
    {
        return [
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'formatted_address' => 'Jl. Contoh Sofa No. 1, Jakarta, Indonesia',
            'city' => 'Jakarta',
            'district' => 'Gambir',
            'postal_code' => '10110',
        ];
    }

    private function payableOrder(int $stock = 5, int $reserved = 2, int $quantity = 2): array
    {
        $customer = User::factory()->create(['name' => 'Customer Sofa']);
        [$product, $variant] = $this->activeProductAndVariant(price: 2500000, stock: $stock, reserved: $reserved);
        $subtotal = (float) $variant->price * $quantity;
        $shippingCost = 100000;

        $order = Order::factory()->for($customer)->create([
            'customer_name' => $customer->name,
            'customer_phone' => '081234567890',
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'shipping_cost' => $shippingCost,
            'total_amount' => $subtotal + $shippingCost,
            'order_status' => 'menunggu_pembayaran',
            'payment_status' => 'pending',
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

        $payment = Payment::factory()->for($order)->create([
            'attempt_number' => 1,
            'midtrans_order_id' => $order->order_number.'-ATTEMPT-1',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => $order->total_amount,
            'snap_token' => 'fake-snap-token-'.$order->order_number.'-ATTEMPT-1',
        ]);

        return [$customer, $order, $variant, $payment];
    }

    private function callbackPayload(Payment $payment, string $transactionStatus, array $overrides = []): array
    {
        return array_merge([
            'order_id' => $payment->midtrans_order_id,
            'status_code' => '200',
            'gross_amount' => number_format((float) $payment->gross_amount, 2, '.', ''),
            'signature_valid' => true,
            'transaction_status' => $transactionStatus,
            'transaction_id' => 'TRX-'.$payment->id,
            'payment_type' => 'bank_transfer',
        ], $overrides);
    }

    private function activeProductAndVariant(int $price, int $stock, int $reserved = 0): array
    {
        $product = Product::factory()->create(['status' => 'aktif']);
        $variant = ProductVariant::factory()->for($product)->create([
            'variant_name' => 'Premium',
            'price' => $price,
            'stock' => $stock,
            'reserved_stock' => $reserved,
            'status' => 'aktif',
        ]);

        return [$product, $variant];
    }
}
