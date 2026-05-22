<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminShippingAndShipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_filter_and_delete_store_radius_from_map_coordinates(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.shipping-areas.store'), [
                'name' => 'Jakarta Pusat',
                'description' => 'Area operasional utama.',
                'latitude' => -6.2,
                'longitude' => 106.816666,
                'radius_km' => 8,
                'shipping_cost' => 150000,
                'priority' => 5,
                'is_active' => true,
            ])
            ->assertRedirect();

        $area = Store::where('name', 'Jakarta Pusat')->firstOrFail();

        $this->assertDatabaseHas('stores', [
            'id' => $area->id,
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'radius_km' => 8,
            'shipping_cost' => 150000,
            'priority' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.shipping-areas.update', $area), [
                'name' => 'Jakarta Pusat Update',
                'description' => 'Catatan operasional update.',
                'radius_km' => 9,
                'shipping_cost' => 175000,
                'priority' => 6,
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('stores', [
            'id' => $area->id,
            'name' => 'Jakarta Pusat Update',
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'radius_km' => 9,
            'shipping_cost' => 175000,
            'priority' => 6,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.shipping-areas.index', ['keyword' => 'Update', 'is_active' => '0']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShippingAreas/Index')
                ->has('areas.data', 1)
                ->where('areas.data.0.name', 'Jakarta Pusat Update')
                ->where('filters.is_active', '0')
                ->missing('googleMaps')
            );

        $this->actingAs($admin)
            ->delete(route('admin.shipping-areas.destroy', $area))
            ->assertRedirect();

        $this->assertSoftDeleted('stores', ['id' => $area->id]);
    }

    public function test_active_overlapping_store_radius_cannot_share_same_priority(): void
    {
        $admin = User::factory()->admin()->create();

        Store::factory()->create([
            'name' => 'Area Utama',
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'radius_km' => 10,
            'priority' => 9,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.shipping-areas.store'), [
                'name' => 'Area Bentrok',
                'description' => null,
                'latitude' => -6.2005,
                'longitude' => 106.816666,
                'radius_km' => 3,
                'shipping_cost' => 100000,
                'priority' => 9,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('priority');

        $this->actingAs($admin)
            ->post(route('admin.shipping-areas.store'), [
                'name' => 'Area Nonaktif',
                'description' => null,
                'latitude' => -6.2005,
                'longitude' => 106.816666,
                'radius_km' => 3,
                'shipping_cost' => 100000,
                'priority' => 9,
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('stores', [
            'name' => 'Area Nonaktif',
            'priority' => 9,
            'is_active' => false,
        ]);
    }

    public function test_shipping_area_management_is_admin_only(): void
    {
        $owner = User::factory()->owner()->create();
        $customer = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('admin.shipping-areas.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.shipping-areas.index'))
            ->assertForbidden();
    }

    public function test_admin_can_list_paid_orders_and_follow_shipment_transitions(): void
    {
        $admin = User::factory()->admin()->create();
        $paidOrder = Order::factory()->paid()->create([
            'customer_name' => 'Budi Sofa',
            'order_number' => 'ORD-PAID-001',
        ]);
        Order::factory()->create([
            'customer_name' => 'Order Pending',
            'order_number' => 'ORD-PENDING-001',
            'payment_status' => 'pending',
            'order_status' => 'menunggu_pembayaran',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.shipments.index', ['keyword' => 'Budi']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Shipments/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.order_number', 'ORD-PAID-001')
                ->where('orders.data.0.shipment_status', 'belum_dijadwalkan')
                ->where('orders.data.0.allowed_statuses', ['belum_dijadwalkan', 'dijadwalkan'])
            );

        $scheduledAt = now()->addDay()->format('Y-m-d\TH:i');

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $paidOrder), [
                'status' => 'dijadwalkan',
                'scheduled_at' => $scheduledAt,
                'delivered_at' => null,
                'driver_name' => 'Pak Andi',
                'driver_phone' => '08123456789',
                'vehicle_note' => 'Pickup toko',
                'shipping_note' => 'Telepon sebelum berangkat.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shipments', [
            'order_id' => $paidOrder->id,
            'status' => 'dijadwalkan',
            'driver_name' => 'Pak Andi',
        ]);
        $this->assertSame('diproses', $paidOrder->fresh()->order_status);

        $this->actingAs($admin)
            ->get(route('admin.shipments.index', ['keyword' => 'Budi']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Shipments/Index')
                ->where('orders.data.0.shipment_status', 'dijadwalkan')
                ->where('orders.data.0.allowed_statuses', ['dijadwalkan', 'dalam_pengiriman', 'gagal_dikirim'])
            );

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $paidOrder), [
                'status' => 'dalam_pengiriman',
                'scheduled_at' => $scheduledAt,
                'delivered_at' => null,
                'driver_name' => 'Pak Andi',
                'driver_phone' => '08123456789',
                'vehicle_note' => 'Pickup toko',
                'shipping_note' => 'Sedang dikirim.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shipments', [
            'order_id' => $paidOrder->id,
            'status' => 'dalam_pengiriman',
        ]);
        $this->assertSame('dikirim', $paidOrder->fresh()->order_status);

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $paidOrder), [
                'status' => 'terkirim',
                'scheduled_at' => $scheduledAt,
                'delivered_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'driver_name' => 'Pak Andi',
                'driver_phone' => '08123456789',
                'vehicle_note' => 'Pickup toko',
                'shipping_note' => 'Diterima customer.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shipments', [
            'order_id' => $paidOrder->id,
            'status' => 'terkirim',
        ]);
        $this->assertSame('selesai', $paidOrder->fresh()->order_status);
    }

    public function test_shipment_rejects_invalid_transition_and_unpaid_order(): void
    {
        $admin = User::factory()->admin()->create();
        $unpaid = Order::factory()->create([
            'payment_status' => 'pending',
            'order_status' => 'menunggu_pembayaran',
        ]);
        $paid = Order::factory()->paid()->create();
        Shipment::factory()->for($paid)->create(['status' => 'belum_dijadwalkan']);

        $payload = [
            'status' => 'terkirim',
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'delivered_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'driver_name' => 'Pak Andi',
            'driver_phone' => '08123456789',
            'vehicle_note' => null,
            'shipping_note' => null,
        ];

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $paid), $payload)
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)
            ->put(route('admin.shipments.update', $unpaid), [
                ...$payload,
                'status' => 'dijadwalkan',
            ])
            ->assertSessionHasErrors('order');
    }

    public function test_shipment_management_is_admin_only(): void
    {
        $owner = User::factory()->owner()->create();
        $customer = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('admin.shipments.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.shipments.index'))
            ->assertForbidden();
    }
}
