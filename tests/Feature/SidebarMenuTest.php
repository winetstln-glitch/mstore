<?php

namespace Tests\Feature;

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
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Act
        $response = $this->actingAs($admin)->get(route('dashboard'));

        // Assert
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            __('Dashboard Center'),
            __('Customer Center'),
            __('Network Operations'),
            __('Ticketing'),
            __('WhatsApp & AI'),
            __('Finance Center'),
            __('HR & Asset'),
            __('Business Units'),
            __('System Administration'),
        ]);

        $response->assertSee(__('Dashboard NOC'));
        $response->assertSee(__('Pendataan Modem'));
        $response->assertSee(__('Kelola Cuti/Izin'));
    }
}
