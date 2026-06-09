<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_has_search_and_expand_logic()
    {
        $role = Role::firstOrCreate(['name' => Role::ADMIN], ['label' => 'Administrator']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $dashboardView = Permission::firstOrCreate(['name' => 'dashboard.view'], ['label' => 'Dashboard View']);
        $role->permissions()->syncWithoutDetaching([$dashboardView->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('id="sidebarSearch"', false);
        $response->assertSee('bootstrap.Collapse.getOrCreateInstance', false);
        $response->assertSee('setCollapseState', false);
        $response->assertSee('sidebar.querySelectorAll(\'.sidebar-header\')', false);
    }
}

