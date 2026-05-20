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
        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('navigationGroups', 6)
                ->where('navigationGroups.0.label', 'Ringkasan')
                ->where('navigationGroups.1.label', 'Produk')
                ->where('navigationGroups.2.label', 'Penjualan')
                ->where('navigationGroups.3.label', 'Pengiriman')
                ->where('navigationGroups.4.label', 'Konten')
                ->where('navigationGroups.5.label', 'Pengguna')
            );
    }

    public function test_owner_dashboard_receives_read_only_sidebar_groups(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('navigationGroups', 3)
                ->where('navigationGroups.0.label', 'Ringkasan')
                ->where('navigationGroups.1.label', 'Laporan')
                ->where('navigationGroups.2.label', 'Monitoring')
                ->missing('navigationGroups.3')
            );
    }
}
