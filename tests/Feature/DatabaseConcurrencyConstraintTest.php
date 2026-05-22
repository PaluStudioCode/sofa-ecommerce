<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseConcurrencyConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_unique_order_numbers_cart_items_and_shipments_are_enforced(): void
    {
        $order = Order::factory()->create(['order_number' => 'ORD-UNIQUE-001']);

        $this->expectQueryException(fn () => Order::factory()->create(['order_number' => 'ORD-UNIQUE-001']));

        $customer = User::factory()->create();
        $product = Product::factory()->create(['status' => 'aktif']);
        $variant = ProductVariant::factory()->for($product)->create(['status' => 'aktif']);

        CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);
        $this->expectQueryException(fn () => CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]));

        Shipment::factory()->for($order)->create();
        $this->expectQueryException(fn () => Shipment::factory()->for($order)->create());
    }

    public function test_payment_attempt_numbers_midtrans_ids_pending_and_success_constraints_are_enforced(): void
    {
        $order = Order::factory()->create();
        Payment::factory()->for($order)->create([
            'attempt_number' => 1,
            'midtrans_order_id' => 'ORD-PAY-1',
            'status' => 'pending',
        ]);

        $this->expectQueryException(fn () => Payment::factory()->for($order)->create([
            'attempt_number' => 1,
            'midtrans_order_id' => 'ORD-PAY-2',
            'status' => 'failed',
        ]));
        $this->expectQueryException(fn () => Payment::factory()->for($order)->create([
            'attempt_number' => 2,
            'midtrans_order_id' => 'ORD-PAY-1',
            'status' => 'failed',
        ]));
        $this->expectQueryException(fn () => Payment::factory()->for($order)->create([
            'attempt_number' => 2,
            'midtrans_order_id' => 'ORD-PAY-3',
            'status' => 'pending',
        ]));

        Payment::query()->where('midtrans_order_id', 'ORD-PAY-1')->update(['status' => 'expired']);
        Payment::factory()->for($order)->create([
            'attempt_number' => 2,
            'midtrans_order_id' => 'ORD-PAY-4',
            'status' => 'success',
        ]);

        $this->expectQueryException(fn () => Payment::factory()->for($order)->create([
            'attempt_number' => 3,
            'midtrans_order_id' => 'ORD-PAY-5',
            'status' => 'success',
        ]));
    }

    public function test_database_rejects_negative_stock_over_reserved_stock_and_preserves_order_snapshots(): void
    {
        $product = Product::factory()->create(['name' => 'Sofa Snapshot', 'status' => 'aktif']);
        $variant = ProductVariant::factory()->for($product)->create([
            'variant_name' => 'Snapshot Variant',
            'price' => 3500000,
            'stock' => 2,
            'reserved_stock' => 1,
            'status' => 'aktif',
        ]);
        $order = Order::factory()->create([
            'subtotal_amount' => 3500000,
            'discount_amount' => 250000,
            'shipping_cost' => 100000,
            'total_amount' => 3350000,
        ]);
        OrderItem::factory()->for($order)->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->variant_name,
            'product_price' => 3500000,
            'quantity' => 1,
            'subtotal' => 3500000,
        ]);

        $this->expectQueryException(fn () => ProductVariant::factory()->for($product)->create([
            'sku' => 'BAD-RESERVED',
            'stock' => 1,
            'reserved_stock' => 2,
        ]));

        $variant->update(['price' => 9999999, 'stock' => 20, 'reserved_stock' => 0]);
        $product->update(['name' => 'Sofa Harga Baru']);

        $order->refresh();
        $item = $order->items()->firstOrFail();

        $this->assertSame(3500000.0, (float) $order->subtotal_amount);
        $this->assertSame(250000.0, (float) $order->discount_amount);
        $this->assertSame(100000.0, (float) $order->shipping_cost);
        $this->assertSame(3350000.0, (float) $order->total_amount);
        $this->assertSame('Sofa Snapshot', $item->product_name);
        $this->assertSame('Snapshot Variant', $item->variant_name);
        $this->assertSame(3500000.0, (float) $item->product_price);
    }

    private function expectQueryException(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected database constraint to throw a query exception.');
        } catch (QueryException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }
    }
}
