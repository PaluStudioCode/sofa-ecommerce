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
                ->has('navigationGroups', 5)
                ->where('navigationGroups.0.label', 'Ringkasan')
                ->where('navigationGroups.1.label', 'Produk')
                ->where('navigationGroups.2.label', 'Penjualan')
                ->where('navigationGroups.3.label', 'Pengiriman')
                ->where('navigationGroups.4.label', 'Pengguna')
                ->has('navigationGroups.1.items', 2)
                ->has('navigationGroups.4.items', 1)
                ->where('navigationGroups.4.items.0.label', 'Pengguna')
            );
    }
}
