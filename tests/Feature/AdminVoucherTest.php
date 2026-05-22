<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminVoucherTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_filter_and_monitor_vouchers(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.vouchers.store'), [
                'code' => ' sofa20 ',
                'name' => 'Promo Sofa Dua Puluh',
                'description' => 'Diskon persentase untuk sofa.',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'max_discount' => 300000,
                'minimum_purchase' => 1000000,
                'quota' => 10,
                'per_user_limit' => 1,
                'start_at' => now()->subDay()->format('Y-m-d\TH:i'),
                'end_at' => now()->addMonth()->format('Y-m-d\TH:i'),
                'status' => 'aktif',
            ])
            ->assertRedirect();

        $voucher = Voucher::where('code', 'SOFA20')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.vouchers.update', $voucher), [
                'code' => 'SOFA25',
                'name' => 'Promo Sofa Dua Lima',
                'description' => 'Update diskon.',
                'discount_type' => 'nominal',
                'discount_value' => 250000,
                'max_discount' => null,
                'minimum_purchase' => 750000,
                'quota' => 12,
                'per_user_limit' => 2,
                'start_at' => now()->subDay()->format('Y-m-d\TH:i'),
                'end_at' => now()->addMonth()->format('Y-m-d\TH:i'),
                'status' => 'aktif',
            ])
            ->assertRedirect(route('admin.vouchers.index'));

        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'code' => 'SOFA25',
            'name' => 'Promo Sofa Dua Lima',
            'discount_type' => 'nominal',
            'discount_value' => 250000,
            'minimum_purchase' => 750000,
            'quota' => 12,
            'per_user_limit' => 2,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.vouchers.index', ['keyword' => 'SOFA25', 'status' => 'aktif', 'discount_type' => 'nominal']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Vouchers/Index')
                ->has('vouchers.data', 1)
                ->where('vouchers.data.0.code', 'SOFA25')
                ->where('vouchers.data.0.used_count', 0)
                ->where('filters.status', 'aktif')
            );
    }

    public function test_voucher_validation_rejects_duplicate_dates_negative_values_percentage_and_low_quota(): void
    {
        $admin = User::factory()->admin()->create();
        $voucher = Voucher::factory()->create(['code' => 'DUPLIKAT', 'used_count' => 2, 'quota' => 5]);

        $base = [
            'code' => 'DUPLIKAT',
            'name' => 'Voucher Salah',
            'discount_type' => 'percentage',
            'discount_value' => 120,
            'max_discount' => -1,
            'minimum_purchase' => -1,
            'quota' => -1,
            'per_user_limit' => -1,
            'start_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'end_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'status' => 'aktif',
        ];

        $this->actingAs($admin)
            ->post(route('admin.vouchers.store'), $base)
            ->assertSessionHasErrors([
                'code',
                'discount_value',
                'max_discount',
                'minimum_purchase',
                'quota',
                'per_user_limit',
                'end_at',
            ]);

        $this->actingAs($admin)
            ->put(route('admin.vouchers.update', $voucher), [
                ...$base,
                'code' => 'DUPLIKAT',
                'discount_value' => 10,
                'max_discount' => 100000,
                'minimum_purchase' => 0,
                'quota' => 1,
                'per_user_limit' => 1,
                'start_at' => now()->subDay()->format('Y-m-d\TH:i'),
                'end_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('quota');
    }

    public function test_status_is_automatically_marked_expired_or_quota_habis(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.vouchers.store'), [
                'code' => 'LAMAPROMO',
                'name' => 'Promo Lama',
                'discount_type' => 'nominal',
                'discount_value' => 100000,
                'max_discount' => null,
                'minimum_purchase' => 0,
                'quota' => 10,
                'per_user_limit' => 1,
                'start_at' => now()->subMonth()->format('Y-m-d\TH:i'),
                'end_at' => now()->subDay()->format('Y-m-d\TH:i'),
                'status' => 'aktif',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('vouchers', [
            'code' => 'LAMAPROMO',
            'status' => 'kedaluwarsa',
        ]);

        Voucher::factory()->create([
            'code' => 'PENUH',
            'quota' => 1,
            'used_count' => 1,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.vouchers.index', ['status' => 'kuota_habis']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('vouchers.data.0.status', 'kuota_habis')
                ->where('vouchers.data.0.code', 'PENUH')
            );

        $this->assertDatabaseHas('vouchers', [
            'code' => 'PENUH',
            'status' => 'kuota_habis',
        ]);
    }

    public function test_used_voucher_is_disabled_instead_of_deleted_and_guest_landing_only_gets_active_voucher(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $active = Voucher::factory()->create([
            'code' => 'AKTIFPROMO',
            'name' => 'Voucher Aktif',
            'status' => 'aktif',
            'quota' => 5,
            'used_count' => 0,
        ]);
        $used = Voucher::factory()->create([
            'code' => 'SUDAHDIPAKAI',
            'status' => 'aktif',
        ]);
        $order = Order::factory()->for($customer)->create(['voucher_id' => $used->id]);
        VoucherUsage::factory()->create([
            'voucher_id' => $used->id,
            'user_id' => $customer->id,
            'order_id' => $order->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.vouchers.destroy', $used))
            ->assertRedirect();

        $this->assertDatabaseHas('vouchers', [
            'id' => $used->id,
            'status' => 'nonaktif',
            'deleted_at' => null,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeVoucher.code', $active->code)
            );
    }

    public function test_voucher_management_is_admin_only(): void
    {
        $owner = User::factory()->owner()->create();
        $customer = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('admin.vouchers.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.vouchers.index'))
            ->assertForbidden();
    }
}
