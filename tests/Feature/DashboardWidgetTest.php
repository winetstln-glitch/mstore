<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_dashboard_requires_permission_and_loads_for_authorized_user(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $permission = Permission::firstOrCreate(['name' => 'dashboard.view'], ['label' => 'View Dashboard', 'group' => 'Dashboard']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_noc_dashboard_requires_permission_and_loads_for_authorized_user(): void
    {
        $role = Role::firstOrCreate(['name' => 'noc'], ['label' => 'Network Operations Center']);
        $permission = Permission::firstOrCreate(['name' => 'noc.dashboard.view'], ['label' => 'View NOC Dashboard', 'group' => 'NOC Center']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get(route('noc.dashboard'))
            ->assertOk();
    }

    public function test_whatsapp_analytics_requires_permission_and_loads_for_authorized_user(): void
    {
        $role = Role::firstOrCreate(['name' => 'finance'], ['label' => 'Finance Staff']);
        $permission = Permission::firstOrCreate(['name' => 'whatsapp.analytics.view'], ['label' => 'View WhatsApp Analytics', 'group' => 'WhatsApp']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get(route('whatsapp.analytics'))
            ->assertOk();
    }
}

