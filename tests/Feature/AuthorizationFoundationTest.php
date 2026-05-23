<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthorizationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_internal_is_only_available_to_admin(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')
            ->assertOk();

    }

    public function test_permission_gate_matches_prd_role_matrix(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->assertFalse(Gate::forUser($customer)->allows('view_dashboard'));
        $this->assertFalse(Gate::forUser($customer)->allows('view_sensitive_customer_data'));

        $this->assertTrue(Gate::forUser($admin)->allows('view_dashboard'));
        $this->assertTrue(Gate::forUser($admin)->allows('manage_products'));
        $this->assertTrue(Gate::forUser($admin)->allows('manage_users'));
        $this->assertTrue(Gate::forUser($admin)->allows('view_sensitive_customer_data'));
    }

    public function test_permission_middleware_rejects_direct_requests_without_permission(): void
    {
        Route::middleware(['web', 'auth', 'permission:manage_products'])
            ->get('/_phase2/manage-products', fn () => 'ok');

        $this->actingAs(User::factory()->create())
            ->get('/_phase2/manage-products')
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/_phase2/manage-products')
            ->assertOk();
    }

    public function test_role_middleware_rejects_direct_requests_for_wrong_role(): void
    {
        Route::middleware(['web', 'auth', 'role:customer'])
            ->get('/_phase2/customer-only', fn () => 'ok');

        $this->get('/_phase2/customer-only')->assertRedirect('/login');

        $this->actingAs(User::factory()->admin()->create())
            ->get('/_phase2/customer-only')
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get('/_phase2/customer-only')
            ->assertOk();
    }
}
