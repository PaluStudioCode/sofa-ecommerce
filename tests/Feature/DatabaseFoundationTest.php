<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\LandingSection;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\ShippingArea;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_one_tables_and_key_columns_exist(): void
    {
        foreach ([
            'users',
            'categories',
            'products',
            'product_variants',
            'product_images',
            'cart_items',
            'vouchers',
            'voucher_usages',
            'shipping_areas',
            'orders',
            'order_items',
            'payments',
            'shipments',
            'landing_sections',
            'notifications',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table [{$table}].");
        }

        $this->assertTrue(Schema::hasColumns('users', ['phone', 'role', 'deleted_at']));
        $this->assertTrue(Schema::hasColumns('orders', ['shipping_latitude', 'shipping_longitude', 'subtotal_amount', 'discount_amount', 'shipping_cost', 'total_amount']));
        $this->assertTrue(Schema::hasColumns('payments', ['attempt_number', 'midtrans_order_id', 'snap_token', 'raw_response']));
    }

    public function test_all_primary_factories_can_create_related_records(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $variant = ProductVariant::factory()->for($product)->create(['reserved_stock' => 0]);
        $image = ProductImage::factory()->for($product)->create();
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);
        $voucher = Voucher::factory()->create();
        $area = ShippingArea::factory()->create();
        $order = Order::factory()->for($user)->for($area, 'shippingArea')->create(['voucher_id' => $voucher->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);
        $payment = Payment::factory()->create(['order_id' => $order->id, 'gross_amount' => $order->total_amount]);
        $shipment = Shipment::factory()->create(['order_id' => $order->id]);
        $usage = VoucherUsage::factory()->create([
            'voucher_id' => $voucher->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);
        $landingSection = LandingSection::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id, 'order_id' => $order->id]);

        $this->assertSame($category->id, $product->category->id);
        $this->assertSame($product->id, $variant->product->id);
        $this->assertSame($product->id, $image->product->id);
        $this->assertSame($variant->id, $cartItem->variant->id);
        $this->assertSame($order->id, $orderItem->order->id);
        $this->assertSame($order->id, $payment->order->id);
        $this->assertSame($order->id, $shipment->order->id);
        $this->assertSame($order->id, $usage->order->id);
        $this->assertTrue($landingSection->is_active);
        $this->assertSame('whatsapp', $notification->channel);
    }
}
