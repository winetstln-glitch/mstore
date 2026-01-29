<?php

namespace Tests\Feature;

use App\Models\Coordinator;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Region;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $coordinatorUser;
    protected $regionA;
    protected $regionB;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles and Permissions
        $role = Role::create(['name' => 'coordinator', 'label' => 'Coordinator']);
        $permCreate = Permission::create(['name' => 'customer.create', 'label' => 'Create Customer', 'group' => 'Customer']);
        $permEdit = Permission::create(['name' => 'customer.edit', 'label' => 'Edit Customer', 'group' => 'Customer']);
        $permView = Permission::create(['name' => 'customer.view', 'label' => 'View Customer', 'group' => 'Customer']);
        $role->permissions()->attach([$permCreate->id, $permEdit->id, $permView->id]);

        $this->regionA = Region::create(['name' => 'Region A']);
        $this->regionB = Region::create(['name' => 'Region B']);

        $this->coordinatorUser = User::factory()->create();
        $this->coordinatorUser->assignRole('coordinator');
        
        Coordinator::create([
            'user_id' => $this->coordinatorUser->id,
            'name' => 'Coord A',
            'region_id' => $this->regionA->id,
        ]);
    }

    public function test_coordinator_can_only_see_odps_in_their_region()
    {
        $olt = Olt::create(['name' => 'OLT 1', 'host' => '1.1.1.1', 'type' => 'epon', 'brand' => 'zte']);
        
        $odcA = Odc::create(['name' => 'ODC A', 'olt_id' => $olt->id, 'region_id' => $this->regionA->id, 'pon_port' => 1]);
        $odcB = Odc::create(['name' => 'ODC B', 'olt_id' => $olt->id, 'region_id' => $this->regionB->id, 'pon_port' => 2]);

        $odpA = Odp::create(['name' => 'ODP A', 'odc_id' => $odcA->id, 'region_id' => $this->regionA->id]);
        $odpB = Odp::create(['name' => 'ODP B', 'odc_id' => $odcB->id, 'region_id' => $this->regionB->id]);

        $response = $this->actingAs($this->coordinatorUser)->get(route('customers.create'));

        $response->assertStatus(200);
        $response->assertSee($odpA->name);
        $response->assertDontSee($odpB->name);
    }

    public function test_coordinator_can_only_see_olts_with_odcs_in_their_region()
    {
        $oltA = Olt::create(['name' => 'OLT Region A', 'host' => '1.1.1.1', 'type' => 'epon', 'brand' => 'zte']);
        $oltB = Olt::create(['name' => 'OLT Region B', 'host' => '2.2.2.2', 'type' => 'epon', 'brand' => 'zte']);

        // OLT A has ODC in Region A
        Odc::create(['name' => 'ODC A', 'olt_id' => $oltA->id, 'region_id' => $this->regionA->id, 'pon_port' => 1]);
        
        // OLT B has ODC in Region B only
        Odc::create(['name' => 'ODC B', 'olt_id' => $oltB->id, 'region_id' => $this->regionB->id, 'pon_port' => 1]);

        $response = $this->actingAs($this->coordinatorUser)->get(route('customers.create'));

        $response->assertStatus(200);
        $response->assertSee('OLT Region A');
        $response->assertDontSee('OLT Region B');
    }

    public function test_coordinator_can_only_see_odps_in_their_region_on_edit_page()
    {
        $olt = Olt::create(['name' => 'OLT 1', 'host' => '1.1.1.1', 'type' => 'epon', 'brand' => 'zte']);
        $odcA = Odc::create(['name' => 'ODC A', 'olt_id' => $olt->id, 'region_id' => $this->regionA->id, 'pon_port' => 1]);
        $odpA = Odp::create(['name' => 'ODP A', 'odc_id' => $odcA->id, 'region_id' => $this->regionA->id]);
        
        $customer = \App\Models\Customer::create([
            'name' => 'Cust A', 
            'odp_id' => $odpA->id,
            'status' => 'active',
            'package' => 'Basic'
        ]);

        $odcB = Odc::create(['name' => 'ODC B', 'olt_id' => $olt->id, 'region_id' => $this->regionB->id, 'pon_port' => 2]);
        $odpB = Odp::create(['name' => 'ODP B', 'odc_id' => $odcB->id, 'region_id' => $this->regionB->id]);

        $response = $this->actingAs($this->coordinatorUser)->get(route('customers.edit', $customer));

        $response->assertStatus(200);
        $response->assertSee($odpA->name);
        $response->assertDontSee($odpB->name);
    }
}
