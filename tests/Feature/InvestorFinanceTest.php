<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Coordinator;
use App\Models\Investor;
use App\Models\Transaction;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestorFinanceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $coordinator;
    protected $investor;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Role and Permission
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        Permission::create(['name' => 'investor.view', 'label' => 'View Investor']);
        Permission::create(['name' => 'investor.manage', 'label' => 'Manage Investor']);
        Permission::create(['name' => 'finance.view', 'label' => 'View Finance']);
        Permission::create(['name' => 'finance.manage', 'label' => 'Manage Finance']);
        
        $role->permissions()->attach(Permission::all()->pluck('id'));

        $this->user = User::factory()->create(['role_id' => $role->id]);
        
        $region = \App\Models\Region::create(['name' => 'Test Region']);

        $this->coordinator = Coordinator::create([
            'name' => 'Test Coordinator',
            'user_id' => $this->user->id,
            'region_id' => $region->id,
            'address' => 'Test Address',
        ]);

        $this->investor = Investor::create([
            'coordinator_id' => $this->coordinator->id,
            'name' => 'Test Investor',
            'phone' => '123456789',
            'description' => 'Test Description',
        ]);

        // Seed settings
        Setting::create(['key' => 'commission_coordinator_percent', 'value' => '15', 'type' => 'number', 'group' => 'finance']);
        Setting::create(['key' => 'commission_isp_percent', 'value' => '25', 'type' => 'number', 'group' => 'finance']);
        Setting::create(['key' => 'commission_tool_percent', 'value' => '15', 'type' => 'number', 'group' => 'finance']);
        Setting::create(['key' => 'commission_investor_percent', 'value' => '50', 'type' => 'number', 'group' => 'finance']);
    }

    public function test_investor_financial_summary_in_index()
    {
        $this->actingAs($this->user);

        // 1. Initial Capital (Income)
        Transaction::create([
            'user_id' => $this->user->id,
            'type' => 'income',
            'category' => 'Initial Capital',
            'amount' => 1000000,
            'transaction_date' => now(),
            'investor_id' => $this->investor->id,
            'coordinator_id' => $this->coordinator->id,
        ]);

        // 2. Withdrawal/Expense
        Transaction::create([
            'user_id' => $this->user->id,
            'type' => 'expense',
            'category' => 'Profit Share',
            'amount' => 200000,
            'transaction_date' => now(),
            'investor_id' => $this->investor->id,
            'coordinator_id' => $this->coordinator->id,
        ]);

        $response = $this->get(route('investors.index'));
        $response->assertOk();
        
        // Assert view has investors data
        $response->assertViewHas('investors');
        
        $investors = $response->viewData('investors');
        $investor = $investors->first();
        
        $this->assertEquals(1000000, $investor->income_transactions_sum_amount);
        $this->assertEquals(200000, $investor->expense_transactions_sum_amount);
        
        // Net Balance check
        $this->assertEquals(800000, $investor->income_transactions_sum_amount - $investor->expense_transactions_sum_amount);
        
        // Check if text appears in response
        $response->assertSee(number_format(1000000, 0, ',', '.'));
        $response->assertSee(number_format(800000, 0, ',', '.'));
    }

    public function test_investor_profit_share_calculation()
    {
        $this->actingAs($this->user);

        // Scenario:
        // Income: 100,000
        // Coord (15%): 15,000 -> Rem1: 85,000
        // ISP (25% of Rem1): 21,250 -> Rem2: 63,750
        // Tool (15% of Rem2): 9,562.5 -> Rem3: 54,187.5
        // Investor (50% of Rem3): 27,093.75

        $response = $this->post(route('finance.store'), [
            'type' => 'income',
            'category' => 'Member Income',
            'amount' => 100000,
            'transaction_date' => now()->toDateString(),
            'coordinator_id' => $this->coordinator->id,
            'investor_id' => $this->investor->id, // Explicitly selecting investor
            'description' => 'Profit Share Test',
        ]);

        $response->assertStatus(302);

        // Verify Allocations
        $this->assertDatabaseHas('transactions', ['category' => 'Coordinator Commission', 'amount' => 15000]);
        $this->assertDatabaseHas('transactions', ['category' => 'ISP Payment', 'amount' => 21250]);
        $this->assertDatabaseHas('transactions', ['category' => 'Tool Fund', 'amount' => 9562.5]);
        
        // Verify Investor Share
        $this->assertDatabaseHas('transactions', [
            'category' => 'Investor Profit Share',
            'amount' => 27093.75,
            'investor_id' => $this->investor->id,
            'type' => 'expense'
        ]);
    }
}
