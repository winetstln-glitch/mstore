<?php

namespace Tests\Feature;

use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Permission;
use App\Models\Role;
use Tests\TestCase;

class OdpAutoNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_odp_name_is_auto_generated()
    {
        // Setup permissions
        $role = Role::create([
            'name' => 'admin',
            'label' => 'Administrator'
        ]);
        $permission = Permission::create([
            'name' => 'odp.create',
            'label' => 'Create ODP',
            'group' => 'odp'
        ]);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Setup Data
        $region = Region::create([
            'name' => 'Cibadak',
            'abbreviation' => 'CBD'
        ]);

        $olt = Olt::create([
            'name' => 'OLT-01',
            'host' => '192.168.1.1',
            'port' => 23,
            'username' => 'admin',
            'password' => 'admin',
            'type' => 'epon',
            'brand' => 'zte',
            'is_active' => true,
        ]);

        $odc = Odc::create([
            'name' => 'ODC-01-CI-L-01',
            'latitude' => 0,
            'longitude' => 0,
            'olt_id' => $olt->id,
            'pon_port' => 'PON 01',
            'area' => 'CIBADAK',
            'color' => 'LIGHT BLUE',
            'cable_no' => '01',
            'capacity' => 144
        ]);

        // Act
        // We explicitly pass odp_area and odp_cable to override ODC attributes
        $response = $this->actingAs($user)->post(route('odps.store'), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'region_id' => $region->id,
            'odc_id' => $odc->id,
            'kampung' => 'Pasir Ipis',
            'color' => 'Blue', // B
            'odp_area' => 'XYZ', // XY
            'odp_cable' => '99', // 99
            'name' => '', // Empty name to trigger generation
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('odps', [
            'region_id' => $region->id,
            'odc_id' => $odc->id,
            'kampung' => 'Pasir Ipis',
            // Name format: ODP-[AREA]-[CABLE]-[COLOR]/[SEQ]
            // Area: XY (from input), Cable: 99 (from input), Color: B (from input), Seq: 01
            // ODP-XY-99-B/01
            'name' => 'ODP-XY-99-B/01'
        ]);
    }

    public function test_odp_name_generation_with_default_abbreviation()
    {
        // Setup permissions
        $role = Role::create([
            'name' => 'admin',
            'label' => 'Administrator'
        ]);
        $permission = Permission::create([
            'name' => 'odp.create',
            'label' => 'Create ODP',
            'group' => 'odp'
        ]);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Setup Data (No Abbreviation)
        $region = Region::create([
            'name' => 'Sukabumi',
            // 'abbreviation' => null
        ]);

        $olt = Olt::create([
            'name' => 'OLT-02',
            'host' => '192.168.1.2',
            'port' => 23,
            'username' => 'admin',
            'password' => 'admin',
            'type' => 'epon',
            'brand' => 'zte',
            'is_active' => true,
        ]);

        $odc = Odc::create([
            'name' => 'ODC-02',
            'latitude' => 0,
            'longitude' => 0,
            'olt_id' => $olt->id,
            'pon_port' => 'PON 02',
            'area' => 'SUKABUMI',
            'color' => 'RED',
            'cable_no' => '02',
            'capacity' => 144
        ]);

        // Act
        $response = $this->actingAs($user)->post(route('odps.store'), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'region_id' => $region->id,
            'odc_id' => $odc->id,
            'kampung' => 'Kota',
            'color' => 'Red',
            'odp_area' => 'SU', // SU
            'odp_cable' => '02', // 02
            'name' => '',
        ]);

        // Assert
        $this->assertDatabaseHas('odps', [
            // Name format: ODP-[AREA]-[CABLE]-[COLOR]/[SEQ]
            // Area: SU, Cable: 02, Color: R, Seq: 01
            // ODP-SU-02-R/01
            'name' => 'ODP-SU-02-R/01'
        ]);
    }
}
