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

        // Check order: Tickets -> Network Monitor -> OLT Management -> Network & Ops
        // We use assertSeeInOrder to verify the sequence in the HTML
        $response->assertSeeInOrder([
            'Tickets',
            'Network Monitor',
            'OLT Management',
            'Network & Ops' // This header appears if admin has tech permissions (admin has all)
        ]);
        
        // Also check Administration comes after
        $response->assertSeeInOrder([
            'OLT Management',
            'Administration'
        ]);
    }
}
