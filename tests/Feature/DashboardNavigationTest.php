<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_receives_admin_sidebar_groups(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('navigationGroups', 4)
                ->where('navigationGroups.0.label', 'Ringkasan')
                ->where('navigationGroups.1.label', 'Katalog')
                ->where('navigationGroups.2.label', 'Penjualan')
                ->where('navigationGroups.3.label', 'Sistem')
                ->has('navigationGroups.0.items', 1)
                ->where('navigationGroups.0.items.0.label', 'Dashboard')
                ->has('navigationGroups.1.items', 2)
                ->where('navigationGroups.1.items.0.label', 'Produk')
                ->where('navigationGroups.1.items.1.label', 'Kategori')
                ->has('navigationGroups.2.items', 2)
                ->where('navigationGroups.2.items.0.label', 'Pesanan')
                ->where('navigationGroups.2.items.1.label', 'Voucher')
                ->has('navigationGroups.3.items', 3)
                ->where('navigationGroups.3.items.0.label', 'Aturan Ongkir Radius')
                ->where('navigationGroups.3.items.1.label', 'Pengaturan Sistem')
                ->where('navigationGroups.3.items.2.label', 'Pengguna')
            );
    }
}
