<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_public_props_do_not_leak_backend_credentials(): void
    {
        config([
            'services.midtrans.client_key' => 'phase16-midtrans-client-key',
            'services.midtrans.server_key' => 'phase16-midtrans-server-key',
            'services.fonnte.token' => 'phase16-fonnte-token',
        ]);

        $customer = User::factory()->create();
        [$product, $variant] = $this->activeProductAndVariant(price: 1500000, stock: 3);
        CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($customer)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Index')
                ->where('midtrans.clientKey', 'phase16-midtrans-client-key')
                ->missing('midtrans.server_key')
                ->missing('midtrans.serverKey')
                ->missing('googleMaps')
            );

        $response
            ->assertDontSee('phase16-midtrans-server-key', false)
            ->assertDontSee('phase16-fonnte-token', false);
    }

    public function test_checkout_recalculates_totals_and_ignores_manual_coordinate_payloads(): void
    {
        $customer = User::factory()->create(['name' => 'Customer Phase 16']);
        [$product, $variant] = $this->activeProductAndVariant(price: 2000000, stock: 4);
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
            'shipping_cost' => 125000,
            'priority' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->post(route('checkout.location'), $this->locationPayload())
            ->assertRedirect(route('checkout.index'));

        $this->actingAs($customer)
            ->post(route('checkout.store'), [
                'customer_phone' => '081234567890',
                'shipping_note' => 'Valid note',
                'shipping_latitude' => 1.234567,
                'shipping_longitude' => 9.876543,
                'subtotal_amount' => 1,
                'discount_amount' => 9999999,
                'shipping_cost' => 1,
                'total_amount' => 1,
            ])
            ->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertSame(4000000.0, (float) $order->subtotal_amount);
        $this->assertSame(0.0, (float) $order->discount_amount);
        $this->assertSame(125000.0, (float) $order->shipping_cost);
        $this->assertSame(4125000.0, (float) $order->total_amount);
        $this->assertSame(-6.2, (float) $order->shipping_latitude);
        $this->assertSame(106.816666, (float) $order->shipping_longitude);
    }

    public function test_negative_business_inputs_and_spoofed_uploads_are_rejected(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        [$product, $variant] = $this->activeProductAndVariant(price: 1000000, stock: 3);

        $this->actingAs($customer)
            ->post(route('cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 0,
            ])
            ->assertSessionHasErrors('quantity');

        $this->actingAs($admin)
            ->post(route('admin.shipping-areas.store'), [
                'name' => 'Area Negatif',
                'description' => null,
                'latitude' => -6.2,
                'longitude' => 106.816666,
                'radius_km' => -1,
                'shipping_cost' => -1,
                'priority' => -1,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['radius_km', 'shipping_cost', 'priority']);

        $this->actingAs($admin)
            ->post(route('admin.variants.store'), [
                'product_id' => $product->id,
                'sku' => 'SEC-NEG-001',
                'variant_name' => 'Negatif',
                'size' => '2 Seater',
                'material' => 'Linen',
                'color' => 'Abu',
                'price' => -1,
                'stock' => -1,
                'status' => 'aktif',
            ])
            ->assertSessionHasErrors(['price', 'stock']);

        $this->actingAs($admin)
            ->post(route('admin.vouchers.store'), [
                'code' => 'NEGATIF',
                'name' => 'Voucher Negatif',
                'description' => null,
                'discount_type' => 'percentage',
                'discount_value' => -1,
                'max_discount' => -1,
                'minimum_purchase' => -1,
                'quota' => -1,
                'per_user_limit' => -1,
                'start_at' => now()->subDay()->format('Y-m-d\TH:i'),
                'end_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'status' => 'aktif',
            ])
            ->assertSessionHasErrors(['discount_value', 'max_discount', 'minimum_purchase', 'quota', 'per_user_limit']);

        $this->actingAs($admin)
            ->post(route('admin.product-images.store'), [
                'product_id' => $product->id,
                'image' => UploadedFile::fake()->create('spoof.jpg', 12, 'application/pdf'),
                'alt_text' => 'Spoof',
                'sort_order' => 0,
                'is_primary' => false,
            ])
            ->assertSessionHasErrors('image');

        $this->actingAs($admin)
            ->post(route('admin.landing-sections.store'), [
                'key' => 'hero',
                'title' => 'Hero',
                'subtitle' => 'Subtitle',
                'body' => 'Body',
                'button_label' => null,
                'button_url' => null,
                'image' => UploadedFile::fake()->create('hero.webp', 12, 'application/pdf'),
                'sort_order' => 0,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_sensitive_routes_enforce_auth_roles_and_customer_ownership(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        $order = Order::factory()->for($otherCustomer)->create([
            'order_status' => 'menunggu_pembayaran',
            'payment_status' => 'pending',
        ]);

        $this->get(route('cart.index'))->assertRedirect('/login');
        $this->get(route('checkout.index'))->assertRedirect('/login');
        $this->get(route('orders.index'))->assertRedirect('/login');

        $this->actingAs($admin)
            ->get(route('cart.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.products.index'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('admin.products.store'), [])
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertNotFound();

        $this->actingAs($customer)
            ->post(route('payments.store', $order), ['return_to' => 'order'])
            ->assertNotFound();
    }

    public function test_production_error_responses_do_not_expose_internal_details(): void
    {
        config([
            'app.debug' => false,
            'app.key' => 'base64:YWJjZGVmZ2hpamtsbW5vcHFyc3R1dnd4eXoxMjM0NTY=',
            'services.midtrans.server_key' => 'phase16-midtrans-server-key',
            'services.fonnte.token' => 'phase16-fonnte-token',
        ]);

        Route::middleware('web')->get('/_phase16-production-error', function () {
            throw new RuntimeException('phase16 internal failure with secret-like marker');
        });

        $this->get('/_phase16-production-error')
            ->assertStatus(500)
            ->assertDontSee(base_path(), false)
            ->assertDontSee('RuntimeException', false)
            ->assertDontSee('phase16 internal failure', false)
            ->assertDontSee('phase16-application-key', false)
            ->assertDontSee('phase16-midtrans-server-key', false)
            ->assertDontSee('phase16-fonnte-token', false);
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

    private function activeProductAndVariant(int $price, int $stock): array
    {
        $product = Product::factory()->create(['status' => 'aktif']);
        $variant = ProductVariant::factory()->for($product)->create([
            'variant_name' => 'Premium',
            'price' => $price,
            'stock' => $stock,
            'reserved_stock' => 0,
            'status' => 'aktif',
        ]);

        return [$product, $variant];
    }
}
