<?php

namespace Tests\Feature;

use App\Models\Coordinator;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Role;
use App\Models\Permission;
use Tests\TestCase;

class FinanceBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $coordinatorUser;
    protected $coordinator;
    protected $region;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Region
        $this->region = Region::create(['name' => 'Test Region']);

        // Setup Admin
        $this->admin = User::factory()->create(['name' => 'Admin User']);
        $roleAdmin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin->assignRole($roleAdmin);

        // Setup Coordinator
        $this->coordinatorUser = User::factory()->create(['name' => 'Coordinator User']);
        $roleCoordinator = Role::create(['name' => 'coordinator', 'guard_name' => 'web']);
        $this->coordinatorUser->assignRole($roleCoordinator);

        $this->coordinator = Coordinator::create([
            'name' => 'Test Coordinator',
            'user_id' => $this->coordinatorUser->id,
            'region_id' => $this->region->id,
        ]);
    }

    public function test_admin_can_bulk_delete_transactions()
    {
        $this->actingAs($this->admin);

        // Create transactions
        $transactions = collect();
        for ($i = 0; $i < 3; $i++) {
            $transactions->push(Transaction::create([
                'user_id' => $this->admin->id,
                'type' => 'income',
                'category' => 'Salary',
                'amount' => 100000,
                'transaction_date' => now(),
                'description' => 'Test Transaction ' . $i,
                'reference_number' => 'REF-' . $i,
            ]));
        }

        $idsToDelete = $transactions->pluck('id')->toArray();

        $response = $this->delete(route('finance.bulkDestroy'), [
            'ids' => $idsToDelete,
        ]);

        $response->assertRedirect(route('finance.index'));
        $response->assertSessionHas('success');

        foreach ($idsToDelete as $id) {
            $this->assertDatabaseMissing('transactions', ['id' => $id]);
        }
    }

    public function test_bulk_delete_removes_related_transactions()
    {
        $this->actingAs($this->admin);

        // Create transaction with related commission/isp/tool
        
        $mainTransaction = Transaction::create([
            'user_id' => $this->admin->id,
            'type' => 'income',
            'category' => 'Member Income',
            'amount' => 100000,
            'transaction_date' => now(),
            'description' => 'Main Transaction',
            'reference_number' => 'REF-123',
        ]);

        $related = [
            'COM-' . $mainTransaction->id,
            'ISP-' . $mainTransaction->id,
            'TOOL-' . $mainTransaction->id,
            'INV-' . $mainTransaction->id,
        ];

        foreach ($related as $ref) {
            Transaction::create([
                'user_id' => $this->admin->id,
                'type' => 'expense',
                'category' => 'Related Expense',
                'amount' => 10000,
                'transaction_date' => now(),
                'description' => 'Related Transaction',
                'reference_number' => $ref,
            ]);
        }

        $response = $this->delete(route('finance.bulkDestroy'), [
            'ids' => [$mainTransaction->id],
        ]);

        $this->assertDatabaseMissing('transactions', ['id' => $mainTransaction->id]);
        foreach ($related as $ref) {
            $this->assertDatabaseMissing('transactions', ['reference_number' => $ref]);
        }
    }

    public function test_non_admin_cannot_bulk_delete()
    {
        $this->actingAs($this->coordinatorUser);

        $transaction = Transaction::create([
            'user_id' => $this->coordinatorUser->id,
            'type' => 'income',
            'category' => 'Salary',
            'amount' => 50000,
            'transaction_date' => now(),
            'description' => 'Coordinator Transaction',
        ]);

        $response = $this->delete(route('finance.bulkDestroy'), [
            'ids' => [$transaction->id],
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_admin_sees_checkboxes()
    {
        $this->actingAs($this->admin);
        
        Transaction::create([
            'user_id' => $this->admin->id,
            'type' => 'income',
            'category' => 'Salary',
            'amount' => 100000,
            'transaction_date' => now(),
            'description' => 'Test Transaction',
        ]);

        $response = $this->get(route('finance.index'));

        $response->assertSee('id="selectAll"', false);
        $response->assertSee('class="form-check-input select-row"', false);
        $response->assertSee('id="bulkDeleteBtn"', false);
    }

    public function test_coordinator_does_not_see_checkboxes()
    {
        $this->actingAs($this->coordinatorUser);
        
        Transaction::create([
            'user_id' => $this->coordinatorUser->id,
            'coordinator_id' => $this->coordinator->id,
            'type' => 'income',
            'category' => 'Salary',
            'amount' => 100000,
            'transaction_date' => now(),
            'description' => 'Coordinator Transaction',
        ]);

        $response = $this->get(route('finance.index'));

        $response->assertDontSee('id="selectAll"', false);
        $response->assertDontSee('class="form-check-input select-row"', false);
        $response->assertDontSee('id="bulkDeleteBtn"', false);
    }
}
