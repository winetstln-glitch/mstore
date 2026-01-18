<?php

namespace Tests\Feature;

use App\Models\Coordinator;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCoordinatorReportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $permission = Permission::create(['name' => 'finance.view', 'label' => 'View Finance', 'group' => 'Finance']);
        $role->permissions()->attach($permission);
        
        $this->user = User::factory()->create(['role_id' => $role->id]);

        $region = Region::create(['name' => 'Test Region', 'code' => 'TR']);
        $this->coordinator = Coordinator::create([
            'name' => 'Test Coordinator',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'region_id' => $region->id
        ]);
    }

    public function test_can_view_coordinator_detail()
    {
        $response = $this->actingAs($this->user)->get(route('finance.coordinator.detail', $this->coordinator->id));
        $response->assertStatus(200);
        $response->assertViewIs('finance.coordinator_detail');
        $response->assertSee($this->coordinator->name);
    }

    public function test_can_download_coordinator_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('finance.coordinator.pdf', $this->coordinator->id));
        $response->assertStatus(200);
        // PDF response content type check
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'application/pdf'));
    }
}
