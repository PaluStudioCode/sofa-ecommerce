<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LandingSection;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinalVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_seed_data_contains_required_demo_records(): void
    {
        $this->seed();

        $this->assertTrue(Hash::check('password', User::where('email', 'customer@sofa.test')->firstOrFail()->password));
        $this->assertTrue(Hash::check('password', User::where('email', 'admin@sofa.test')->firstOrFail()->password));

        $this->assertDatabaseHas('users', ['email' => 'customer@sofa.test', 'role' => 'customer']);
        $this->assertDatabaseHas('users', ['email' => 'admin@sofa.test', 'role' => 'admin']);
        $this->assertDatabaseMissing('users', ['role' => 'owner']);
        $this->assertGreaterThanOrEqual(1, Category::count());
        $this->assertGreaterThanOrEqual(1, Product::where('status', 'aktif')->count());
        $this->assertGreaterThanOrEqual(1, Product::where('status', 'nonaktif')->count());
        $this->assertGreaterThanOrEqual(1, ProductVariant::where('status', 'aktif')->count());
        $this->assertGreaterThanOrEqual(1, ProductVariant::where('status', 'stok_habis')->count());
        $this->assertGreaterThanOrEqual(1, ProductImage::where('is_primary', true)->count());
        $this->assertStringStartsWith('https://images.unsplash.com/', ProductImage::where('is_primary', true)->firstOrFail()->file_path);
        $this->assertGreaterThanOrEqual(1, Voucher::where('status', 'aktif')->count());
        $this->assertGreaterThanOrEqual(1, Voucher::where('status', 'kedaluwarsa')->count());
        $this->assertGreaterThanOrEqual(1, Store::where('is_active', true)->count());
        $this->assertGreaterThanOrEqual(1, LandingSection::where('is_active', true)->count());
        $this->assertStringStartsWith('https://images.unsplash.com/', LandingSection::where('section_key', 'hero')->firstOrFail()->image_path);
        $this->assertGreaterThanOrEqual(1, Order::count());
        $this->assertGreaterThanOrEqual(1, Notification::where('event_type', 'order_created')->count());
    }

    public function test_seeded_customer_and_admin_can_login_to_expected_areas(): void
    {
        $this->seed();

        $this->post('/login', [
            'email' => 'customer@sofa.test',
            'password' => 'password',
        ])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs(User::where('email', 'customer@sofa.test')->firstOrFail());
        $this->post('/logout');

        $this->post('/login', [
            'email' => 'admin@sofa.test',
            'password' => 'password',
        ])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs(User::where('email', 'admin@sofa.test')->firstOrFail());
        $this->post('/logout');

    }

    public function test_final_seeded_public_customer_and_admin_flows_are_reachable(): void
    {
        $this->seed();

        $customer = User::where('email', 'customer@sofa.test')->firstOrFail();
        $admin = User::where('email', 'admin@sofa.test')->firstOrFail();
        $product = Product::where('status', 'aktif')->firstOrFail();
        $order = Order::where('user_id', $customer->id)->firstOrFail();

        $this->get(route('home'))->assertOk();
        $this->get(route('catalog.index'))->assertOk();
        $this->get(route('products.show', $product->slug))->assertOk();
        $this->get(route('checkout.index'))->assertRedirect('/login');
        $this->get(route('dashboard'))->assertRedirect('/login');

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Orders/Show')
                ->where('order.id', $order->id)
                ->missing('order.shipping_latitude')
                ->missing('order.shipping_longitude')
            );

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Show')
                ->where('order.id', $order->id)
                ->has('order.shipping_latitude')
                ->has('order.shipping_longitude')
            );

        $this->actingAs($customer)
            ->post(route('admin.products.store'), [])
            ->assertForbidden();
    }
}
