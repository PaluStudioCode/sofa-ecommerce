<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminVoucherTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_filter_and_monitor_vouchers(): void
    {
        $admin = $this->user('admin');

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
        $admin = $this->user('admin');
        $voucher = $this->voucher(['code' => 'DUPLIKAT', 'quota' => 5]);
        $this->voucherOrder($voucher);
        $this->voucherOrder($voucher);

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
        $admin = $this->user('admin');

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

        $full = $this->voucher(['code' => 'PENUH', 'quota' => 1, 'status' => 'aktif']);
        $this->voucherOrder($full);

        $this->actingAs($admin)
            ->get(route('admin.vouchers.index', ['status' => 'kuota_habis']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('vouchers.data.0.status', 'kuota_habis')
                ->where('vouchers.data.0.code', 'PENUH')
            );
    }

    public function test_used_voucher_is_disabled_instead_of_deleted_and_guest_home_only_gets_active_voucher(): void
    {
        $admin = $this->user('admin');
        $active = $this->voucher([
            'code' => 'AKTIFPROMO',
            'name' => 'Voucher Aktif',
            'status' => 'aktif',
            'quota' => 5,
        ]);
        $used = $this->voucher(['code' => 'SUDAHDIPAKAI', 'status' => 'aktif']);
        $this->voucherOrder($used);

        $this->actingAs($admin)
            ->delete(route('admin.vouchers.destroy', $used))
            ->assertRedirect();

        $this->assertDatabaseHas('vouchers', [
            'id' => $used->id,
            'status' => 'nonaktif',
            'deleted_at' => null,
        ]);

        auth()->logout();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeVoucher.code', $active->code)
            );
    }

    public function test_voucher_management_is_admin_only(): void
    {
        $customer = $this->user('customer');

        $this->actingAs($customer)
            ->get(route('admin.vouchers.index'))
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

    private function voucher(array $overrides = []): Voucher
    {
        return Voucher::create([
            'code' => $overrides['code'] ?? 'VOUCHER'.uniqid(),
            'name' => $overrides['name'] ?? 'Voucher Test',
            'description' => $overrides['description'] ?? null,
            'discount_type' => $overrides['discount_type'] ?? 'nominal',
            'discount_value' => $overrides['discount_value'] ?? 100000,
            'max_discount' => $overrides['max_discount'] ?? null,
            'minimum_purchase' => $overrides['minimum_purchase'] ?? 0,
            'quota' => $overrides['quota'] ?? null,
            'per_user_limit' => $overrides['per_user_limit'] ?? null,
            'start_at' => $overrides['start_at'] ?? now()->subDay(),
            'end_at' => $overrides['end_at'] ?? now()->addDay(),
            'status' => $overrides['status'] ?? 'aktif',
        ]);
    }

    private function voucherOrder(Voucher $voucher): Order
    {
        $customer = $this->user('customer');
        $order = Order::create([
            'order_number' => 'ORD-'.uniqid(),
            'user_id' => $customer->id,
            'order_status' => 'diproses',
        ]);

        $order->voucherSnapshot()->create([
            'voucher_id' => $voucher->id,
            'voucher_code' => $voucher->code,
            'voucher_name' => $voucher->name,
        ]);

        return $order;
    }
}
