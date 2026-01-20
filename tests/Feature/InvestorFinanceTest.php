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

        // Add a second investor to trigger Cash Fund Deduction (5%)
        Investor::create([
            'coordinator_id' => $this->coordinator->id,
            'name' => 'Second Investor',
            'phone' => '987654321',
        ]);

        $response = $this->post(route('finance.store'), [
            'type' => 'income',
            'category' => 'Member Income',
            'amount' => 125000000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Test Income',
            'coordinator_id' => $this->coordinator->id,
            'investor_id' => $this->investor->id,
        ]);

        $response->assertRedirect(route('finance.index'));

        // Calculation Check:
        // Gross: 125,000,000
        // Coord (15%): 18,750,000 -> Rem1: 106,250,000
        // ISP (25%): 26,562,500 -> Rem2: 79,687,500
        // Tool (15%): 11,953,125 -> Rem3: 67,734,375
        // Since InvestorCount > 1:
        // Investor Cash (5% of Rem3): 3,386,718.75
        // Rem4: 64,347,656.25
        // Investor Share (100% of Rem4): 64,347,656.25

        // Verify Investor Cash Fund (5% of Total Rem3)
        $this->assertDatabaseHas('transactions', [
            'category' => 'Investor Cash Fund',
            'amount' => 3386718.75, // 5% of Rem3
            'coordinator_id' => $this->coordinator->id,
            'investor_id' => null, // Should not be linked to investor
            'type' => 'expense'
        ]);

        // Verify Investor Profit Share
        $this->assertDatabaseHas('transactions', [
            'category' => 'Investor Profit Share',
            'amount' => 64347656.25,
            'investor_id' => $this->investor->id,
        ]);
    }

    public function test_single_investor_has_cash_fund_deduction()
    {
        $this->actingAs($this->user);

        // Ensure only one investor exists for this coordinator (created in setUp)

        $response = $this->post(route('finance.store'), [
            'type' => 'income',
            'category' => 'Member Income',
            'amount' => 10000000, // 10 Million
            'transaction_date' => now()->toDateString(),
            'description' => 'Single Investor Income',
            'coordinator_id' => $this->coordinator->id,
            'investor_id' => $this->investor->id,
        ]);

        $response->assertRedirect(route('finance.index'));

        // Calculation Check:
        // Gross: 10,000,000
        // Coord (15%): 1,500,000 -> Rem1: 8,500,000
        // ISP (25%): 2,125,000 -> Rem2: 6,375,000
        // Tool (15%): 956,250 -> Rem3: 5,418,750
        // Investor Count == 1 BUT Cash Fund still applies (5%)
        // Cash Fund = 5% of 5,418,750 = 270,937.5
        // Rem4 = 5,147,812.5
        // Investor Share (100% of Rem4): 5,147,812.5

        // Verify Investor Cash Fund EXISTS
        $this->assertDatabaseHas('transactions', [
            'category' => 'Investor Cash Fund',
            'amount' => 270937.5,
            'coordinator_id' => $this->coordinator->id,
            'investor_id' => null,
            'type' => 'expense'
        ]);

        // Verify Investor Profit Share
        $this->assertDatabaseHas('transactions', [
            'category' => 'Investor Profit Share',
            'amount' => 5147812.5,
            'investor_id' => $this->investor->id,
        ]);
    }
}
