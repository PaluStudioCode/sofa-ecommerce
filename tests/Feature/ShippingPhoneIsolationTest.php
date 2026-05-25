<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingPhoneIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_shipping_address_phone_does_not_change_profile_phone(): void
    {
        $user = $this->createCustomer('080000000001');

        $this->actingAs($user)
            ->post(route('address.update'), [
                'recipient_name' => 'Penerima Sofa',
                'phone' => '089999999999',
                'detail' => 'Rumah pagar putih',
                'formatted_address' => 'Jl. Sofa No. 1, Palu',
                'city' => 'Palu',
                'district' => 'Palu Selatan',
                'postal_code' => '94111',
                'latitude' => -0.9003,
                'longitude' => 119.878,
            ])
            ->assertRedirect(route('address.edit'));

        $this->assertSame('080000000001', $user->fresh()->phone);
        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'phone' => '089999999999',
            'is_default' => true,
        ]);
    }

    public function test_creating_order_uses_shipping_phone_without_changing_profile_phone(): void
    {
        $user = $this->createCustomer('080000000001');
        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'recipient_name' => 'Penerima Sofa',
            'phone' => '089999999999',
            'detail' => 'Rumah pagar putih',
            'formatted_address' => 'Jl. Sofa No. 1, Palu',
            'city' => 'Palu',
            'district' => 'Palu Selatan',
            'postal_code' => '94111',
            'latitude' => -0.9003,
            'longitude' => 119.878,
            'is_default' => true,
        ]);
        $variant = $this->createAvailableVariant();
        CartItem::query()->create([
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);
        ShippingSetting::query()->create([
            'origin_name' => 'Toko Sofa Palu',
            'origin_address' => 'Palu',
            'origin_latitude' => -0.9003,
            'origin_longitude' => 119.878,
            'radius_km' => 25,
            'shipping_cost_per_km' => 12000,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.store'))
            ->assertRedirect();

        $this->assertSame('080000000001', $user->fresh()->phone);
        $this->assertDatabaseHas('order_addresses', [
            'user_address_id' => $address->id,
            'phone' => '089999999999',
        ]);
    }

    private function createCustomer(string $phone): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Customer Sofa',
            'email' => 'customer@example.test',
            'phone' => $phone,
            'password' => 'password',
            'role' => 'customer',
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    private function createAvailableVariant(): ProductVariant
    {
        $category = Category::query()->create([
            'name' => 'Sofa',
            'slug' => 'sofa',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Sofa Test',
            'slug' => 'sofa-test',
            'description' => 'Sofa untuk test checkout.',
            'status' => 'aktif',
            'is_featured' => false,
        ]);

        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'SOFA-TEST-001',
            'variant_name' => 'Default',
            'price' => 1000000,
            'stock' => 5,
            'reserved_stock' => 0,
            'status' => 'aktif',
        ]);
    }
}
