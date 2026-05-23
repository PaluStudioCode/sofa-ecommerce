<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

    public function test_checkout_page_shows_cart_items_without_map_api_credentials(): void
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
                ->missing('googleMaps')
            );
    }

    public function test_checkout_quote_applies_voucher_and_active_shipping_radius_rule(): void
    {
        $customer = User::factory()->create();
        [$product, $variant] = $this->activeProductAndVariant(price: 2000000, stock: 5);
        CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        Store::factory()->create([
            'name' => 'Toko Utama',
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'radius_km' => 10,
            'shipping_cost' => 75000,
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

        $this->saveCustomerAddress($customer);

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
                ->where('summary.store.name', 'Toko Utama')
                ->where('summary.store.shipping_cost_per_km', 75000)
                ->where('summary.store.billable_distance_km', 1)
                ->where('summary.voucher.code', 'HEMAT250')
                ->where('summary.can_submit', true)
            );
    }

    public function test_shipping_cost_uses_rounded_distance_times_rate_per_km(): void
    {
        $customer = User::factory()->create();
        [$product, $variant] = $this->activeProductAndVariant(price: 1000000, stock: 3);
        CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        Store::factory()->create([
            'name' => 'Toko Utama',
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'radius_km' => 10,
            'shipping_cost' => 20000,
            'is_active' => true,
        ]);

        $this->saveCustomerAddress($customer, ['latitude' => -6.2378]);

        $this->actingAs($customer)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.store.billable_distance_km', 4)
                ->where('summary.shipping_cost', 80000)
                ->where('summary.total', 1080000)
            );

        $this->saveCustomerAddress($customer, ['latitude' => -6.2414]);

        $this->actingAs($customer->fresh())
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.store.billable_distance_km', 5)
                ->where('summary.shipping_cost', 100000)
                ->where('summary.total', 1100000)
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

        Store::factory()->create([
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'radius_km' => 10,
            'shipping_cost' => 100000,
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

        $this->saveCustomerAddress($customer);
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

    public function test_customer_can_manage_shipping_address_on_address_page(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('address.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Address/Edit')
                ->where('address', null)
            );

        $this->actingAs($customer)
            ->post(route('address.update'), $this->locationPayload([
                'latitude' => -0.915403,
                'longitude' => 119.877033,
                'formatted_address' => 'RT 02, RW 07, Tatua Selatan, Kecamatan Palu Selatan, Palu, Sulawesi Tengah, Indonesia',
                'city' => 'Palu',
                'district' => 'Tatua Selatan',
                'postal_code' => '94236',
            ]))
            ->assertRedirect(route('address.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'shipping_address' => 'RT 02, RW 07, Tatua Selatan, Kecamatan Palu Selatan, Palu, Sulawesi Tengah, Indonesia',
            'shipping_city' => 'Palu',
            'shipping_district' => 'Tatua Selatan',
            'shipping_postal_code' => '94236',
            'shipping_latitude' => -0.915403,
            'shipping_longitude' => 119.877033,
        ]);

        $this->actingAs($customer->fresh())
            ->get(route('address.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Address/Edit')
                ->where('address.formatted_address', 'RT 02, RW 07, Tatua Selatan, Kecamatan Palu Selatan, Palu, Sulawesi Tengah, Indonesia')
                ->where('address.city', 'Palu')
                ->where('address.latitude', -0.915403)
                ->where('address.longitude', 119.877033)
            );
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

        Store::factory()->create([
            'latitude' => -7.5,
            'longitude' => 110.0,
            'radius_km' => 1,
            'shipping_cost' => 100000,
            'is_active' => true,
        ]);

        $this->saveCustomerAddress($customer);

        $this->actingAs($customer)
            ->post(route('checkout.quote'), ['voucher_code' => 'TIDAKADA'])
            ->assertSessionHasErrors('location');

        Store::query()->delete();
        Store::factory()->create([
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'radius_km' => 10,
            'shipping_cost' => 100000,
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

    public function test_reverse_geocode_route_proxies_address_lookup_through_backend(): void
    {
        Cache::flush();

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'display_name' => 'Poboya, Kecamatan Mantikulore, Palu, Central Sulawesi, Sulawesi, 94118, Indonesia',
                'address' => [
                    'village' => 'Poboya',
                    'district' => 'Kecamatan Mantikulore',
                    'postcode' => '94118',
                ],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('maps.reverse-geocode', [
                'latitude' => -0.860163,
                'longitude' => 119.918175,
            ]))
            ->assertOk()
            ->assertJson([
                'formatted_address' => 'Poboya, Kecamatan Mantikulore, Palu, Central Sulawesi, Sulawesi, 94118, Indonesia',
                'city' => 'Poboya',
                'district' => 'Kecamatan Mantikulore',
                'postal_code' => '94118',
            ]);

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://nominatim.openstreetmap.org/reverse')
            && str_contains($request->url(), 'lat=-0.86016300')
            && str_contains($request->url(), 'lon=119.91817500'));

        $this->actingAs(User::factory()->create())
            ->getJson(route('maps.reverse-geocode', [
                'latitude' => -0.860163,
                'longitude' => 119.918175,
            ]))
            ->assertOk()
            ->assertJsonPath('city', 'Poboya');

        Http::assertSentCount(1);
    }

    private function locationPayload(array $overrides = []): array
    {
        return [
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'formatted_address' => 'Jl. Contoh Sofa No. 1, Jakarta, Indonesia',
            'city' => 'Jakarta',
            'district' => 'Gambir',
            'postal_code' => '10110',
            ...$overrides,
        ];
    }

    private function saveCustomerAddress(User $customer, array $overrides = []): User
    {
        $payload = $this->locationPayload($overrides);

        $customer->forceFill([
            'shipping_address' => $payload['formatted_address'],
            'shipping_city' => $payload['city'],
            'shipping_district' => $payload['district'],
            'shipping_postal_code' => $payload['postal_code'],
            'shipping_latitude' => $payload['latitude'],
            'shipping_longitude' => $payload['longitude'],
        ])->save();

        return $customer->refresh();
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
