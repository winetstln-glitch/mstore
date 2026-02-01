<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_menu_order()
    {
        // Create Admin Role and User
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Act
        $response = $this->actingAs($admin)->get(route('dashboard'));

        // Assert
        $response->assertStatus(200);

        // Check for "Main Menu" header
        $response->assertSee('Main Menu');

        $response->assertSeeInOrder([__('Tickets'), __('Network Management')]);
        $response->assertSeeInOrder([__('Network Management'), __('Administration')]);

        $response->assertSee(__('Monitoring Genieacs'));
        $response->assertSee(__('Management Router'));
        $response->assertSee(__('OLT Management'));
    }
}
