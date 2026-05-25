<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ShippingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminShippingAndShipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_and_update_single_shipping_setting_from_map_coordinates(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->post(route('admin.shipping-areas.store'), [
                'name' => 'Toko Utama',
                'description' => 'Aturan ongkir utama.',
                'latitude' => -6.2,
                'longitude' => 106.816666,
                'radius_km' => 8,
                'shipping_cost' => 20000,
            ])
            ->assertRedirect();

        $setting = ShippingSetting::where('origin_name', 'Toko Utama')->firstOrFail();

        $this->assertDatabaseHas('shipping_settings', [
            'id' => $setting->id,
            'origin_name' => 'Toko Utama',
            'origin_latitude' => -6.2,
            'origin_longitude' => 106.816666,
            'radius_km' => 8,
            'shipping_cost_per_km' => 20000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.shipping-areas.update', $setting), [
                'name' => 'Toko Utama Update',
                'description' => 'Catatan operasional update.',
                'radius_km' => 9,
                'shipping_cost' => 25000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shipping_settings', [
            'id' => $setting->id,
            'origin_name' => 'Toko Utama Update',
            'origin_latitude' => -6.2,
            'origin_longitude' => 106.816666,
            'radius_km' => 9,
            'shipping_cost_per_km' => 25000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.shipping-areas.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShippingAreas/Index')
                ->where('setting.name', 'Toko Utama Update')
                ->where('setting.shipping_cost_per_km', 25000)
            );

        $this->assertFalse(Route::has('admin.shipping-areas.destroy'));
    }

    public function test_store_updates_existing_shipping_setting_instead_of_adding_new_rule(): void
    {
        $admin = $this->user('admin');
        $setting = ShippingSetting::create([
            'origin_name' => 'Aturan Lama',
            'origin_address' => null,
            'origin_latitude' => -6.2,
            'origin_longitude' => 106.816666,
            'radius_km' => 10,
            'shipping_cost_per_km' => 20000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.shipping-areas.store'), [
                'name' => 'Aturan Baru',
                'description' => null,
                'latitude' => -6.2005,
                'longitude' => 106.816666,
                'radius_km' => 3,
                'shipping_cost' => 25000,
            ])
            ->assertRedirect();

        $this->assertSame(1, ShippingSetting::count());
        $this->assertSame('Aturan Baru', $setting->fresh()->origin_name);
        $this->assertSame(1, ShippingSetting::where('is_active', true)->count());
    }

    public function test_admin_can_update_shipment_details_for_paid_order(): void
    {
        $admin = $this->user('admin');
        $paidOrder = $this->order(paymentStatus: 'success', orderStatus: 'diproses');

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $paidOrder), [
                'order_status' => 'dalam_perjalanan',
                'scheduled_at' => now()->addDay()->toDateString(),
                'delivered_at' => null,
                'driver_name' => 'Pak Andi',
                'driver_phone' => '08123456789',
                'vehicle_note' => 'Pickup toko',
                'shipping_note' => 'Telepon sebelum berangkat.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_deliveries', [
            'order_id' => $paidOrder->id,
            'driver_name' => 'Pak Andi',
            'driver_phone' => '08123456789',
        ]);
        $this->assertSame('dalam_perjalanan', $paidOrder->fresh()->order_status);
    }

    public function test_shipment_rejects_invalid_transition_and_unpaid_order(): void
    {
        $admin = $this->user('admin');
        $paid = $this->order(paymentStatus: 'success', orderStatus: 'diproses');
        $unpaid = $this->order(paymentStatus: 'pending', orderStatus: 'menunggu_pembayaran');

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $paid), [
                'order_status' => 'barang_diterima',
                'scheduled_at' => now()->addDay()->toDateString(),
                'delivered_at' => now()->addDays(2)->toDateString(),
            ])
            ->assertSessionHasErrors('order_status');

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $unpaid), [
                'order_status' => 'dalam_perjalanan',
                'scheduled_at' => now()->addDay()->toDateString(),
            ])
            ->assertSessionHasErrors('order');
    }

    public function test_shipping_area_management_is_admin_only(): void
    {
        $customer = $this->user('customer');

        $this->actingAs($customer)
            ->get(route('admin.shipping-areas.index'))
            ->assertForbidden();
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $role.'-'.uniqid().'@example.test',
            'phone' => '08123456789',
            'role' => $role,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }

    private function order(string $paymentStatus, string $orderStatus): Order
    {
        $customer = $this->user('customer');
        $order = Order::create([
            'order_number' => 'ORD-'.uniqid(),
            'user_id' => $customer->id,
            'order_status' => $orderStatus,
        ]);

        $order->total()->create([
            'subtotal_amount' => 1000000,
            'discount_amount' => 0,
            'shipping_cost' => 100000,
            'total_amount' => 1100000,
        ]);

        $order->address()->create([
            'recipient_name' => $customer->name,
            'phone' => $customer->phone,
            'detail' => 'Rumah contoh.',
            'formatted_address' => 'Jl. Sofa No. 1',
            'latitude' => -6.2,
            'longitude' => 106.816666,
        ]);

        if ($paymentStatus !== 'none') {
            DB::table('payments')->insert([
                'order_id' => $order->id,
                'attempt_number' => 1,
                'midtrans_order_id' => $order->order_number.'-ATTEMPT-1',
                'status' => $paymentStatus,
                'transaction_status' => $paymentStatus === 'success' ? 'settlement' : 'pending',
                'gross_amount' => 1100000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $order->fresh();
    }
}
