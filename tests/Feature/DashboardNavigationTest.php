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
                ->has('navigationGroups', 5)
                ->where('navigationGroups.0.label', 'Ringkasan')
                ->where('navigationGroups.1.label', 'Katalog')
                ->where('navigationGroups.2.label', 'Penjualan')
                ->where('navigationGroups.3.label', 'Operasional')
                ->where('navigationGroups.4.label', 'Sistem')
                ->has('navigationGroups.1.items', 2)
                ->has('navigationGroups.2.items', 3)
                ->has('navigationGroups.4.items', 2)
                ->where('navigationGroups.4.items.0.label', 'Pengaturan Sistem')
                ->where('navigationGroups.4.items.1.label', 'Pengguna')
            );
    }
}
