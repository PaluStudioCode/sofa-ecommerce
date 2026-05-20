<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingArea;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_and_customer_cannot_checkout_empty_cart(): void
    {
        $this->get(route('checkout.index'))->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'));
    }

    public function test_checkout_page_shows_cart_items_and_safe_google_maps_config(): void
    {
        $customer = User::factory()->create();
        [$product, $variant] = $this->activeProductAndVariant(price: 2500000, stock: 4);
        CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->actingAs($customer)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Index')
                ->has('items', 1)
                ->where('items.0.product_name', $product->name)
                ->where('summary.subtotal', 5000000)
                ->where('summary.can_submit', false)
                ->has('googleMaps.apiKey')
            );
    }

    public function test_checkout_quote_applies_voucher_and_highest_priority_shipping_area(): void
    {
        $customer = User::factory()->create();
        [$product, $variant] = $this->activeProductAndVariant(price: 2000000, stock: 5);
        CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        ShippingArea::factory()->create([
            'name' => 'Area Prioritas Rendah',
            'center_latitude' => -6.2,
            'center_longitude' => 106.816666,
            'radius_km' => 10,
            'shipping_cost' => 150000,
            'priority' => 1,
            'is_active' => true,
        ]);
        ShippingArea::factory()->create([
            'name' => 'Area Prioritas Tinggi',
            'center_latitude' => -6.2,
            'center_longitude' => 106.816666,
            'radius_km' => 10,
            'shipping_cost' => 75000,
            'priority' => 9,
            'is_active' => true,
        ]);

        Voucher::factory()->create([
            'code' => 'HEMAT250',
            'discount_type' => 'nominal',
            'discount_value' => 250000,
            'minimum_purchase' => 1000000,
            'quota' => 10,
            'used_count' => 0,
            'per_user_limit' => 1,
            'status' => 'aktif',
        ]);

        $this->actingAs($customer)
            ->post(route('checkout.location'), ['place_id' => 'fake-place'])
            ->assertRedirect(route('checkout.index'));

        $this->actingAs($customer)
            ->post(route('checkout.quote'), ['voucher_code' => 'HEMAT250'])
            ->assertRedirect(route('checkout.index'));

        $this->actingAs($customer)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('location.formatted_address', 'Jl. Contoh Sofa No. 1, Jakarta, Indonesia')
                ->where('summary.subtotal', 4000000)
                ->where('summary.discount_amount', 250000)
                ->where('summary.shipping_cost', 75000)
                ->where('summary.total', 3825000)
                ->where('summary.shipping_area.name', 'Area Prioritas Tinggi')
                ->where('summary.voucher.code', 'HEMAT250')
                ->where('summary.can_submit', true)
            );
    }

    public function test_checkout_creates_order_snapshots_reserves_stock_and_clears_cart(): void
    {
        $customer = User::factory()->create(['name' => 'Customer Sofa']);
        [$product, $variant] = $this->activeProductAndVariant(price: 3000000, stock: 5, reserved: 1);
        CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        ShippingArea::factory()->create([
            'center_latitude' => -6.2,
            'center_longitude' => 106.816666,
            'radius_km' => 10,
            'shipping_cost' => 100000,
            'priority' => 3,
            'is_active' => true,
        ]);
        $voucher = Voucher::factory()->create([
            'code' => 'SOFA300',
            'discount_type' => 'nominal',
            'discount_value' => 300000,
            'minimum_purchase' => 1000000,
            'quota' => 2,
            'used_count' => 0,
            'per_user_limit' => 1,
            'status' => 'aktif',
        ]);

        $this->actingAs($customer)
            ->post(route('checkout.location'), ['place_id' => 'fake-place'])
            ->assertRedirect(route('checkout.index'));
        $this->actingAs($customer)
            ->post(route('checkout.quote'), ['voucher_code' => 'SOFA300'])
            ->assertRedirect(route('checkout.index'));

        $this->actingAs($customer)
            ->post(route('checkout.store'), [
                'customer_phone' => '081234567890',
                'shipping_note' => 'Blok A nomor 10',
                'voucher_code' => 'SOFA300',
            ])
            ->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $customer->id,
            'voucher_id' => $voucher->id,
            'customer_name' => 'Customer Sofa',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Contoh Sofa No. 1, Jakarta, Indonesia',
            'subtotal_amount' => 6000000,
            'discount_amount' => 300000,
            'shipping_cost' => 100000,
            'total_amount' => 5800000,
            'order_status' => 'menunggu_pembayaran',
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->variant_name,
            'product_price' => 3000000,
            'quantity' => 2,
            'subtotal' => 6000000,
        ]);
        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertSame(3, $variant->fresh()->reserved_stock);
        $this->assertSame(1, $voucher->fresh()->used_count);
        $this->assertSame(1, VoucherUsage::where('order_id', $order->id)->count());
        $this->assertDatabaseMissing('cart_items', ['user_id' => $customer->id]);
    }

    public function test_checkout_validation_rejects_missing_phone_invalid_voucher_outside_area_and_stale_stock(): void
    {
        $customer = User::factory()->create();
        [$product, $variant] = $this->activeProductAndVariant(price: 2000000, stock: 2);
        CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        ShippingArea::factory()->create([
            'center_latitude' => -7.5,
            'center_longitude' => 110.0,
            'radius_km' => 1,
            'shipping_cost' => 100000,
            'priority' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->post(route('checkout.location'), ['place_id' => 'fake-place'])
            ->assertRedirect(route('checkout.index'));

        $this->actingAs($customer)
            ->post(route('checkout.quote'), ['voucher_code' => 'TIDAKADA'])
            ->assertSessionHasErrors('location');

        ShippingArea::query()->delete();
        ShippingArea::factory()->create([
            'center_latitude' => -6.2,
            'center_longitude' => 106.816666,
            'radius_km' => 10,
            'shipping_cost' => 100000,
            'priority' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->post(route('checkout.quote'), ['voucher_code' => 'TIDAKADA'])
            ->assertSessionHasErrors('voucher_code');

        $this->actingAs($customer)
            ->post(route('checkout.store'), [
                'customer_phone' => '',
                'shipping_note' => null,
            ])
            ->assertSessionHasErrors('customer_phone');

        $variant->update(['reserved_stock' => 1]);

        $this->actingAs($customer)
            ->post(route('checkout.store'), [
                'customer_phone' => '081234567890',
                'shipping_note' => null,
            ])
            ->assertSessionHasErrors('cart');
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
