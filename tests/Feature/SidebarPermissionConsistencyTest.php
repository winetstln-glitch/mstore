<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarPermissionConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_modem_menu_requires_modem_permission_not_ticket_view()
    {
        $role = Role::firstOrCreate(['name' => Role::NOC], ['label' => 'NOC']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $dashboardView = Permission::firstOrCreate(['name' => 'dashboard.view'], ['label' => 'Dashboard View']);
        $ticketView = Permission::firstOrCreate(['name' => 'ticket.view'], ['label' => 'Ticket View']);

        $role->permissions()->syncWithoutDetaching([$dashboardView->id, $ticketView->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Tiket Gangguan');
        $response->assertDontSee('Pendataan Modem');

        $modemView = Permission::firstOrCreate(['name' => 'modem-data.view'], ['label' => 'Modem Data View']);
        $role->permissions()->syncWithoutDetaching([$modemView->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Pendataan Modem');
    }
}

