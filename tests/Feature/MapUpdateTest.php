<?php

namespace Tests\Feature;

use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapUpdateTest extends TestCase
{
    // use RefreshDatabase; // Don't use this if you want to keep data, but for testing it's safer. 
    // However, since I don't want to wipe the user's DB, I'll be careful. 
    // Actually, RefreshDatabase wraps in transaction, so it rolls back. Safe.
    use RefreshDatabase; 

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed permissions and roles since we use RefreshDatabase
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_admin_can_update_odc_location()
    {
        $role = Role::where('name', 'admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $olt = Olt::create([
            'name' => 'Test OLT',
            'host' => '192.168.1.1',
            'type' => 'epon',
            'username' => 'admin',
            'password' => 'admin',
            'port' => 80,
            'community_read' => 'public',
            'latitude' => 0,
            'longitude' => 0,
            'is_active' => true,
        ]);

        $odc = Odc::create([
            'name' => 'Test ODC',
            'olt_id' => $olt->id,
            'latitude' => -6.0,
            'longitude' => 106.0,
            'capacity' => 48,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/odcs/{$odc->id}", [
                'name' => 'Test ODC Updated',
                'olt_id' => $olt->id,
                'latitude' => -6.1,
                'longitude' => 106.1,
                'capacity' => 48,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('odcs', [
            'id' => $odc->id,
            'latitude' => -6.1,
            'longitude' => 106.1,
        ]);
    }

    public function test_admin_can_update_odp_location()
    {
        $role = Role::where('name', 'admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $olt = Olt::create([
            'name' => 'Test OLT',
            'host' => '192.168.1.1',
            'type' => 'epon',
            'username' => 'admin',
            'password' => 'admin',
            'port' => 80,
            'community_read' => 'public',
            'latitude' => 0,
            'longitude' => 0,
            'is_active' => true,
        ]);

        $odc = Odc::create([
            'name' => 'Test ODC',
            'olt_id' => $olt->id,
            'latitude' => -6.0,
            'longitude' => 106.0,
            'capacity' => 48,
        ]);

        $odp = Odp::create([
            'name' => 'Test ODP',
            'odc_id' => $odc->id,
            'latitude' => -6.0,
            'longitude' => 106.0,
            'capacity' => 8,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/odps/{$odp->id}", [
                'name' => 'Test ODP Updated',
                'odc_id' => $odc->id,
                'latitude' => -6.1,
                'longitude' => 106.1,
                'capacity' => 8,
            ]);

        $response->assertStatus(200)
             ->assertJson(['success' => true]);

        $this->assertDatabaseHas('odps', [
            'id' => $odp->id,
            'latitude' => -6.1,
            'longitude' => 106.1,
        ]);
    }

    public function test_admin_can_delete_odc_location()
    {
        $role = Role::where('name', 'admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $olt = Olt::create([
            'name' => 'Test OLT Delete',
            'host' => '192.168.1.2',
            'type' => 'epon',
            'username' => 'admin',
            'password' => 'admin',
            'port' => 80,
            'community_read' => 'public',
            'latitude' => 0,
            'longitude' => 0,
            'is_active' => true,
        ]);

        $odc = Odc::create([
            'name' => 'Test ODC Delete',
            'olt_id' => $olt->id,
            'latitude' => -6.0,
            'longitude' => 106.0,
            'capacity' => 48,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/odcs/{$odc->id}", [
                'name' => 'Test ODC Delete',
                'olt_id' => $olt->id,
                'latitude' => null,
                'longitude' => null,
                'capacity' => 48,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('odcs', [
            'id' => $odc->id,
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    public function test_admin_can_delete_odp_location()
    {
        $role = Role::where('name', 'admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $odp = Odp::create([
            'name' => 'Test ODP Delete',
            'latitude' => -6.0,
            'longitude' => 106.0,
            'capacity' => 8,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/odps/{$odp->id}", [
                'name' => 'Test ODP Delete',
                'latitude' => null,
                'longitude' => null,
                'capacity' => 8,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('odps', [
            'id' => $odp->id,
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    public function test_admin_can_update_olt_location()
    {
        $role = Role::where('name', 'admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $olt = Olt::create([
            'name' => 'Test OLT Update',
            'host' => '192.168.1.100',
            'type' => 'epon',
            'username' => 'admin',
            'password' => 'admin',
            'port' => 80,
            'community_read' => 'public',
            'brand' => 'zte',
            'latitude' => null,
            'longitude' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->put("/olt/{$olt->id}", [
                'name' => 'Test OLT Update',
                'host' => '192.168.1.100',
                'type' => 'epon',
                'username' => 'admin',
                'port' => 80,
                'brand' => 'zte',
                'latitude' => -6.5,
                'longitude' => 106.5,
                'is_active' => true,
            ]);

        // OLT update redirects to index
        $response->assertStatus(302);
        
        $this->assertDatabaseHas('olts', [
            'id' => $olt->id,
            'latitude' => -6.5,
            'longitude' => 106.5,
        ]);
    }

    public function test_admin_can_update_olt_location_json()
    {
        $role = Role::where('name', 'admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);

        $olt = Olt::create([
            'name' => 'Test OLT Update JSON',
            'host' => '192.168.1.101',
            'type' => 'epon',
            'username' => 'admin',
            'password' => 'admin',
            'port' => 80,
            'community_read' => 'public',
            'brand' => 'zte',
            'latitude' => null,
            'longitude' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/olt/{$olt->id}", [
                'name' => 'Test OLT Update JSON',
                'host' => '192.168.1.101',
                'type' => 'epon',
                'username' => 'admin',
                'port' => 80,
                'brand' => 'zte',
                'latitude' => -6.6,
                'longitude' => 106.6,
                'is_active' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('olts', [
            'id' => $olt->id,
            'latitude' => -6.6,
            'longitude' => 106.6,
        ]);
    }
}
