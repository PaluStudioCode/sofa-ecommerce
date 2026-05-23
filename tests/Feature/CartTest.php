<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_adding_product_to_cart(): void
    {
        $product = Product::factory()->create(['status' => 'aktif']);
        $variant = ProductVariant::factory()->for($product)->create(['status' => 'aktif', 'stock' => 3, 'reserved_stock' => 0]);

        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect('/login');
    }

    public function test_customer_can_add_variant_to_cart_without_decreasing_stock(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['status' => 'aktif']);
        $variant = ProductVariant::factory()->for($product)->create([
            'status' => 'aktif',
            'stock' => 5,
            'reserved_stock' => 1,
        ]);

        $this->actingAs($customer)
            ->post(route('cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertSame(1, $variant->fresh()->reserved_stock);
    }

    public function test_same_variant_is_merged_instead_of_duplicated(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['status' => 'aktif']);
        $variant = ProductVariant::factory()->for($product)->create(['status' => 'aktif', 'stock' => 5, 'reserved_stock' => 0]);

        CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->actingAs($customer)
            ->post(route('cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('cart.index'));

        $this->assertSame(1, CartItem::where('user_id', $customer->id)->where('product_variant_id', $variant->id)->count());
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $customer->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
    }

    public function test_cart_rejects_inactive_products_inactive_variants_and_insufficient_stock(): void
    {
        $customer = User::factory()->create();
        $inactiveProduct = Product::factory()->inactive()->create();
        $inactiveProductVariant = ProductVariant::factory()->for($inactiveProduct)->create(['status' => 'aktif', 'stock' => 5]);

        $activeProduct = Product::factory()->create(['status' => 'aktif']);
        $inactiveVariant = ProductVariant::factory()->for($activeProduct)->inactive()->create(['stock' => 5]);
        $lowStockVariant = ProductVariant::factory()->for($activeProduct)->create(['status' => 'aktif', 'stock' => 2, 'reserved_stock' => 1]);

        $this->actingAs($customer)
            ->post(route('cart.store'), [
                'product_id' => $inactiveProduct->id,
                'product_variant_id' => $inactiveProductVariant->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product_id');

        $this->actingAs($customer)
            ->post(route('cart.store'), [
                'product_id' => $activeProduct->id,
                'product_variant_id' => $inactiveVariant->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product_variant_id');

        $this->actingAs($customer)
            ->post(route('cart.store'), [
                'product_id' => $activeProduct->id,
                'product_variant_id' => $lowStockVariant->id,
                'quantity' => 2,
            ])
            ->assertSessionHasErrors('quantity');
    }

    public function test_customer_can_view_update_and_remove_only_their_own_cart_items(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create(['status' => 'aktif']);
        $variant = ProductVariant::factory()->for($product)->create(['status' => 'aktif', 'stock' => 4, 'reserved_stock' => 0]);
        $item = CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);
        $otherItem = CartItem::factory()->create(['user_id' => $other->id]);

        $this->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cart/Index')
                ->has('items', 1)
                ->where('items.0.id', $item->id)
                ->where('summary.can_checkout', true)
            );

        $this->actingAs($customer)
            ->patch(route('cart.update', $item), ['quantity' => 3])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 3]);

        $this->actingAs($customer)
            ->patch(route('cart.update', $otherItem), ['quantity' => 2])
            ->assertNotFound();

        $this->actingAs($customer)
            ->delete(route('cart.destroy', $item))
            ->assertRedirect();

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_cart_page_marks_changed_or_invalid_items_before_checkout(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['status' => 'aktif']);
        $variant = ProductVariant::factory()->for($product)->create(['status' => 'aktif', 'stock' => 2, 'reserved_stock' => 0]);
        CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        $this->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('items.0.is_valid', false)
                ->where('summary.can_checkout', false)
            );
    }

    public function test_cart_is_customer_only(): void
    {
        $this->get(route('cart.index'))->assertRedirect('/login');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('cart.index'))
            ->assertForbidden();
    }
}
