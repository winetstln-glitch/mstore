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

class FinanceCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinator_income_generates_commission_expense()
    {
        // Setup permissions
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $permission = Permission::create(['name' => 'finance.manage', 'label' => 'Manage Finance', 'group' => 'Finance']);
        $role->permissions()->attach($permission);
        
        $user = User::factory()->create(['role_id' => $role->id]);
        // $user->roles()->attach($role);

        // Setup Coordinator
        $region = Region::create(['name' => 'Test Region', 'code' => 'TR']);
        $coordinator = Coordinator::create([
            'name' => 'Test Coordinator',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'region_id' => $region->id
        ]);

        // Act: Submit Income Transaction
        $response = $this->actingAs($user)->post(route('finance.store'), [
            'type' => 'income',
            'category' => 'Member Income',
            'amount' => 100000,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Income from members',
            'coordinator_id' => $coordinator->id,
            'reference_number' => 'REF001'
        ]);

        $response->assertRedirect(route('finance.index'));
        $response->assertSessionHas('success');

        // Assert: Check Database
        $this->assertDatabaseHas('transactions', [
            'type' => 'income',
            'amount' => 100000,
            'category' => 'Member Income',
            'coordinator_id' => $coordinator->id,
        ]);

        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'amount' => 15000, // 15% of 100,000
            'category' => 'Coordinator Commission',
            'coordinator_id' => $coordinator->id,
        ]);
    }

    public function test_regular_income_does_not_generate_commission()
    {
        // Setup permissions
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $permission = Permission::create(['name' => 'finance.manage', 'label' => 'Manage Finance', 'group' => 'Finance']);
        $role->permissions()->attach($permission);
        
        $user = User::factory()->create(['role_id' => $role->id]);
        // $user->roles()->attach($role);

        // Act: Submit Regular Income (No Coordinator)
        $response = $this->actingAs($user)->post(route('finance.store'), [
            'type' => 'income',
            'category' => 'General Sales',
            'amount' => 100000,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'General sales',
        ]);

        // Assert: Only 1 transaction
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transactions', [
            'type' => 'income',
            'amount' => 100000,
            'category' => 'General Sales',
        ]);
    }
}
