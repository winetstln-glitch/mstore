<?php

namespace Tests\Feature;

use App\Models\Coordinator;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cascading_commission_calculation()
    {
        // 1. Setup Environment
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $permission = Permission::create(['name' => 'finance.manage', 'label' => 'Manage Finance', 'group' => 'Finance']);
        $role->permissions()->attach($permission);
        
        $user = User::factory()->create(['role_id' => $role->id]);

        $region = Region::create(['name' => 'Test Region', 'code' => 'TR']);
        $coordinator = Coordinator::create([
            'name' => 'Test Coordinator',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'region_id' => $region->id
        ]);

        // 2. Configure Settings
        Setting::updateOrCreate(['key' => 'commission_coordinator_percent'], ['value' => '15']);
        Setting::updateOrCreate(['key' => 'commission_isp_percent'], ['value' => '25']);
        Setting::updateOrCreate(['key' => 'commission_tool_percent'], ['value' => '15']);

        // 3. Act: Create Transaction
        $response = $this->actingAs($user)->post(route('finance.store'), [
            'type' => 'income',
            'category' => 'Member Income',
            'amount' => 100000,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Test Income',
            'coordinator_id' => $coordinator->id,
            'reference_number' => 'REF-TEST-001'
        ]);

        $response->assertRedirect(route('finance.index'));

        // 4. Assert: Check Calculations
        
        // Coordinator Commission: 15% of 100,000 = 15,000
        $this->assertDatabaseHas('transactions', [
            'category' => 'Coordinator Commission',
            'amount' => 15000,
            'coordinator_id' => $coordinator->id,
        ]);

        // Remaining 1: 85,000
        // ISP Payment: 25% of 85,000 = 21,250
        $this->assertDatabaseHas('transactions', [
            'category' => 'ISP Payment',
            'amount' => 21250,
            'coordinator_id' => $coordinator->id,
        ]);

        // Remaining 2: 63,750
        // Tool Fund: 15% of 63,750 = 9,562.5
        $this->assertDatabaseHas('transactions', [
            'category' => 'Tool Fund',
            'amount' => 9562.5,
            'coordinator_id' => $coordinator->id,
        ]);

        // Total Transactions should be 4
        $this->assertEquals(4, Transaction::count());
    }

    public function test_update_recalculates_cascade()
    {
        // Setup (Same as above)
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $permission = Permission::create(['name' => 'finance.manage', 'label' => 'Manage Finance', 'group' => 'Finance']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create(['role_id' => $role->id]);
        $region = Region::create(['name' => 'Test Region', 'code' => 'TR']);
        $coordinator = Coordinator::create([
            'name' => 'Test Coordinator',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'region_id' => $region->id
        ]);
        Setting::updateOrCreate(['key' => 'commission_coordinator_percent'], ['value' => '15']);
        Setting::updateOrCreate(['key' => 'commission_isp_percent'], ['value' => '25']);
        Setting::updateOrCreate(['key' => 'commission_tool_percent'], ['value' => '15']);

        // Create Initial Transaction
        $this->actingAs($user)->post(route('finance.store'), [
            'type' => 'income',
            'category' => 'Member Income',
            'amount' => 100000,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Test Income',
            'coordinator_id' => $coordinator->id,
            'reference_number' => 'REF-TEST-001'
        ]);

        $transaction = Transaction::where('category', 'Member Income')->first();

        // Update Amount to 200,000
        $response = $this->actingAs($user)->put(route('finance.update', $transaction->id), [
            'type' => 'income',
            'category' => 'Member Income',
            'amount' => 200000,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Updated Income',
            'coordinator_id' => $coordinator->id,
            'reference_number' => 'REF-TEST-001'
        ]);

        // Assert New Values
        // Gross: 200,000
        // Coord: 15% of 200k = 30,000 (Rem: 170,000)
        // ISP: 25% of 170k = 42,500 (Rem: 127,500)
        // Tool: 15% of 127,500 = 19,125
        
        $this->assertDatabaseHas('transactions', [
            'category' => 'Coordinator Commission',
            'amount' => 30000,
        ]);

        $this->assertDatabaseHas('transactions', [
            'category' => 'ISP Payment',
            'amount' => 42500,
        ]);

        $this->assertDatabaseHas('transactions', [
            'category' => 'Tool Fund',
            'amount' => 19125,
        ]);
    }
}
