<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SystemSettingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_system_settings_page_only_receives_store_contact_settings(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.system-settings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/SystemSettings/Index')
                ->has('storeContact')
                ->missing('systemInfo')
                ->missing('integrations')
            );
    }
}
