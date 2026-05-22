<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminOperationalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_operational_summary_from_real_data(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['name' => 'Customer Sofa']);
        $incomingOrder = Order::factory()->for($customer)->create([
            'customer_name' => 'Customer Sofa',
            'order_status' => 'menunggu_pembayaran',
            'payment_status' => 'pending',
            'total_amount' => 5100000,
            'created_at' => now(),
        ]);
        Payment::factory()->for($incomingOrder)->create([
            'status' => 'pending',
            'gross_amount' => $incomingOrder->total_amount,
        ]);
        $processingOrder = Order::factory()->for($customer)->create([
            'order_status' => 'diproses',
            'payment_status' => 'success',
            'created_at' => now()->subDay(),
        ]);
        Shipment::factory()->for($processingOrder)->create(['status' => 'dijadwalkan']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.incoming_orders', 1)
                ->where('summary.pending_payments', 1)
                ->where('summary.processing_orders', 1)
                ->where('summary.active_shipments', 1)
                ->where('recentOrders.0.customer_name', 'Customer Sofa')
                ->missing('lowStockVariants')
            );
    }

    public function test_admin_can_create_update_and_deactivate_internal_users_without_exposing_passwords(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Admin Operasional',
                'email' => 'ops@example.com',
                'phone' => '081234567890',
                'role' => 'admin',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertRedirect();

        $internal = User::where('email', 'ops@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password-baru', $internal->password));

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['keyword' => 'ops@example.com']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data', 1)
                ->where('users.data.0.email', 'ops@example.com')
                ->missing('users.data.0.password')
                ->missing('users.data.0.remember_token')
            );

        $this->actingAs($admin)
            ->put(route('admin.users.update', $internal), [
                'name' => 'Owner Monitoring',
                'email' => 'owner-monitor@example.com',
                'phone' => '089999999999',
                'role' => 'owner',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $internal->id,
            'name' => 'Owner Monitoring',
            'email' => 'owner-monitor@example.com',
            'role' => 'owner',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $internal))
            ->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $internal->id]);
    }

    public function test_admin_user_management_rejects_customer_creation_and_forbids_non_admin_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Customer Dari Admin',
                'email' => 'customer-admin@example.com',
                'role' => 'customer',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertSessionHasErrors('role');

        $this->actingAs($owner)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_reserved_stock_is_not_mutated_by_admin_variant_form(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create([
            'reserved_stock' => 2,
            'stock' => 8,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.variants.update', $variant), [
                'product_id' => $product->id,
                'sku' => $variant->sku,
                'variant_name' => 'Premium Updated',
                'size' => '3 seater',
                'material' => 'Linen',
                'color' => 'Abu',
                'price' => 3500000,
                'stock' => 10,
                'reserved_stock' => 7,
                'status' => 'aktif',
            ])
            ->assertRedirect();

        $this->assertSame(2, $variant->fresh()->reserved_stock);
        $this->assertSame(10, $variant->fresh()->stock);
    }
}
