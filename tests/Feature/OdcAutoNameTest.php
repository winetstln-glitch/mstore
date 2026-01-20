<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Olt;
use App\Models\Odc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OdcAutoNameTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $olt;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin User
        $this->user = User::factory()->create();
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        
        // Assign all permissions
        $permissions = [
            'odc.view', 'odc.create', 'odc.edit', 'odc.delete'
        ];
        
        $permissionIds = [];
        foreach ($permissions as $perm) {
            $p = Permission::create(['name' => $perm, 'label' => $perm, 'group' => 'ODC']);
            $permissionIds[] = $p->id;
        }
        
        $role->permissions()->attach($permissionIds);
        $this->user->assignRole($role);

        // Create OLT
        $this->olt = Olt::create([
            'name' => 'OLT-01',
            'host' => '192.168.1.1',
            'type' => 'epon', // Changed to lowercase
            'port' => 8080,
            'username' => 'admin', // Added required field
            'password' => 'password', // Added required field
            'is_active' => true,
        ]);
    }

    public function test_odc_name_is_auto_generated()
    {
        $response = $this->actingAs($this->user)->post(route('odcs.store'), [
            'olt_id' => $this->olt->id,
            'pon_port' => 'PON 01', // Space included
            'area' => 'CI BADAK', // Space included
            'color' => 'LIGHT BLUE', // Space included
            'cable_no' => '0 1', // Space included
            'capacity' => 144,
            'latitude' => -6.200000,
            'longitude' => 106.800000,
            'name' => '', // Empty name to trigger auto-generation
        ]);

        $response->assertRedirect(route('odcs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('odcs', [
            'name' => 'ODC-01-CI-L-01', // Shortened format
            'pon_port' => 'PON 01', // Original input preserved
            'area' => 'CI BADAK',
            'color' => 'LIGHT BLUE',
            'cable_no' => '0 1',
        ]);
    }

    public function test_odc_update_name_regeneration()
    {
        // Create initial ODC
        $odc = Odc::create([
            'name' => 'ODC-PON01-OLD-BLUE-01',
            'olt_id' => $this->olt->id,
            'pon_port' => 'PON01',
            'area' => 'OLD',
            'color' => 'BLUE',
            'cable_no' => '01',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'capacity' => 144,
        ]);

        // Update with new area, empty name
        $response = $this->actingAs($this->user)->put(route('odcs.update', $odc), [
            'olt_id' => $this->olt->id,
            'pon_port' => 'PON02',
            'area' => 'NEWAREA',
            'color' => 'RED',
            'cable_no' => '02',
            'capacity' => 144,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'name' => '', // Trigger regen
        ]);

        $response->assertRedirect(route('odcs.index'));
        
        $this->assertDatabaseHas('odcs', [
            'id' => $odc->id,
            'name' => 'ODC-02-NE-R-02', // Shortened format
            'pon_port' => 'PON02',
            'area' => 'NEWAREA',
            'color' => 'RED',
            'cable_no' => '02',
        ]);
    }
}
