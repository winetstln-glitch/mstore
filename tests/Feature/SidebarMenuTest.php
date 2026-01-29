<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

class SidebarMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed permissions and roles
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_see_all_menus()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $user = User::factory()->create(['role_id' => $adminRole->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Keuangan');
        $response->assertSee('Toko ATK');
        $response->assertSee('Network');
        $response->assertSee('Rekap Absensi');
        $response->assertSee('Manajemen Role');
    }

    public function test_finance_can_see_finance_and_atk_but_limited_system()
    {
        $financeRole = Role::where('name', 'finance')->first();
        $user = User::factory()->create(['role_id' => $financeRole->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Keuangan');
        $response->assertSee('Toko ATK');
        $response->assertSee('Kasir (POS)');
        $response->assertSee('Rekap Absensi'); // Should see because has attendance.report
        
        // Finance has role.view permission? Let's check RoleSeeder
        // Finance has 'setting.view', but does it have 'role.view'?
        // RoleSeeder says: 'role.view' is NOT in the list for Finance.
        // So they should NOT see 'Manajemen Role'.
        $response->assertDontSee('Manajemen Role');
    }

    public function test_technician_can_see_operational_menus_only()
    {
        $techRole = Role::where('name', 'technician')->first();
        $user = User::factory()->create(['role_id' => $techRole->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Tiket & Gangguan');
        $response->assertSee('Absensi Saya');
        $response->assertDontSee('Rekap Absensi'); // Removed attendance.report
        $response->assertDontSee('Keuangan');
        $response->assertDontSee('Manajemen Role');
        $response->assertDontSee('Kasir (POS)');
    }

    public function test_coordinator_menu_visibility()
    {
        $coordRole = Role::where('name', 'coordinator')->first();
        $user = User::factory()->create(['role_id' => $coordRole->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Inventory / Tools');
        $response->assertSee('Peta Jaringan');
        
        // Coordinator has finance.view, so should see Keuangan menu item
        $response->assertSee('Keuangan');
        
        $response->assertDontSee('Manajemen Role');
    }
}
