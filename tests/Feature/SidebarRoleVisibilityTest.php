<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarRoleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_role_sees_finance_center_only_when_granted()
    {
        $role = Role::firstOrCreate(['name' => Role::FINANCE], ['label' => 'Finance']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $dashboardView = Permission::firstOrCreate(['name' => 'dashboard.view'], ['label' => 'Dashboard View']);
        $financeView = Permission::firstOrCreate(['name' => 'finance.view'], ['label' => 'Finance View']);
        $role->permissions()->syncWithoutDetaching([$dashboardView->id, $financeView->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Finance Center');
        $response->assertDontSee('Network Operations');
        $response->assertDontSee('Ticketing');
    }

    public function test_noc_role_sees_network_operations_and_ticketing()
    {
        $role = Role::firstOrCreate(['name' => Role::NOC], ['label' => 'NOC']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $dashboardView = Permission::firstOrCreate(['name' => 'dashboard.view'], ['label' => 'Dashboard View']);
        $nocDashboardView = Permission::firstOrCreate(['name' => 'noc.dashboard.view'], ['label' => 'NOC Dashboard View']);
        $nocOperationalView = Permission::firstOrCreate(['name' => 'noc.operational.view'], ['label' => 'NOC Operational View']);
        $ticketView = Permission::firstOrCreate(['name' => 'ticket.view'], ['label' => 'Ticket View']);

        $role->permissions()->syncWithoutDetaching([
            $dashboardView->id,
            $nocDashboardView->id,
            $nocOperationalView->id,
            $ticketView->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Network Operations');
        $response->assertSee('Ticketing');
        $response->assertDontSee('Finance Center');
    }

    public function test_technician_sees_leave_submission_menu_but_not_leave_management_menu()
    {
        $role = Role::firstOrCreate(['name' => Role::TECHNICIAN], ['label' => 'Technician']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $dashboardView = Permission::firstOrCreate(['name' => 'dashboard.view'], ['label' => 'Dashboard View']);
        $leaveView = Permission::firstOrCreate(['name' => 'leave.view'], ['label' => 'Leave View']);

        $role->permissions()->syncWithoutDetaching([
            $dashboardView->id,
            $leaveView->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Pengajuan Cuti/Izin Saya');
        $response->assertDontSee('Kelola Cuti/Izin');
    }

    public function test_custom_employee_role_with_leave_view_still_sees_leave_submission_menu()
    {
        $role = Role::firstOrCreate(['name' => 'karyawan'], ['label' => 'Karyawan']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $dashboardView = Permission::firstOrCreate(['name' => 'dashboard.view'], ['label' => 'Dashboard View']);
        $leaveView = Permission::firstOrCreate(['name' => 'leave.view'], ['label' => 'Leave View']);

        $role->permissions()->syncWithoutDetaching([
            $dashboardView->id,
            $leaveView->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Pengajuan Cuti/Izin Saya');
    }
}
