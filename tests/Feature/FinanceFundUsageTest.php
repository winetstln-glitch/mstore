<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Coordinator;
use App\Models\Transaction;
use App\Models\Setting;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceFundUsageTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Role and Permission
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        Permission::create(['name' => 'finance.view', 'label' => 'View Finance']);
        Permission::create(['name' => 'finance.manage', 'label' => 'Manage Finance']);
        
        // Custom Role/Permission pivot if needed, but Role model has permissions() relation
        $role->permissions()->attach(Permission::whereIn('name', ['finance.view', 'finance.manage'])->pluck('id'));

        $this->user = User::factory()->create(['role_id' => $role->id]);
        
        // Create Region
        $region = \App\Models\Region::create(['name' => 'Test Region']);

        $this->coordinator = Coordinator::create([
            'name' => 'Test Coordinator',
            'user_id' => $this->user->id,
            'region_id' => $region->id,
            'address' => 'Test Address',
        ]);


        // Seed settings
        Setting::create(['key' => 'commission_coordinator_percent', 'value' => '15', 'type' => 'number', 'group' => 'finance']);
        Setting::create(['key' => 'commission_isp_percent', 'value' => '25', 'type' => 'number', 'group' => 'finance']);
        Setting::create(['key' => 'commission_tool_percent', 'value' => '15', 'type' => 'number', 'group' => 'finance']);
    }

    public function test_fund_usage_logic()
    {
        $this->actingAs($this->user);

        // 1. Create Income Transaction which triggers allocations
        // Amount: 100,000
        // Coord (15%): 15,000
        // Rem1: 85,000
        // ISP (25%): 21,250
        // Rem2: 63,750
        // Tool (15%): 9,562.5
        
        $response = $this->post(route('finance.store'), [
            'type' => 'income',
            'category' => 'Member Income',
            'amount' => 100000,
            'transaction_date' => now()->toDateString(),
            'coordinator_id' => $this->coordinator->id,
            'description' => 'Income Test',
        ]);

        $response->assertStatus(302);

        // Verify Allocations
        $this->assertDatabaseHas('transactions', ['category' => 'ISP Payment', 'amount' => 21250]);
        $this->assertDatabaseHas('transactions', ['category' => 'Tool Fund', 'amount' => 9562.5]);

        // Check Dashboard Data (via Index)
        $response = $this->get(route('finance.index'));
        $response->assertOk();
        
        // Assert initial balances
        // Total Income: 100,000
        // Total Expense (Allocations): 15,000 + 21,250 + 9,562.5 = 45,812.5
        // Balance: 54,187.5
        // ISP Fund: 21,250
        // Tool Fund: 9,562.5

        $response->assertViewHas('totalIncome', 100000);
        $response->assertViewHas('totalExpense', 45812.5);
        $response->assertViewHas('balance', 54187.5);
        $response->assertViewHas('totalIspShare', 21250);
        $response->assertViewHas('totalToolFund', 9562.5);

        // 2. Add "Pembayaran ISP" Transaction (Usage)
        $this->post(route('finance.store'), [
            'type' => 'expense',
            'category' => 'Pembayaran ISP',
            'amount' => 10000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Pay ISP Bill',
        ]);

        // 3. Add "Pembelian Alat" Transaction (Usage)
        $this->post(route('finance.store'), [
            'type' => 'expense',
            'category' => 'Pembelian Alat',
            'amount' => 5000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Buy Tools',
        ]);

        // Check Dashboard Data again
        $response = $this->get(route('finance.index'));
        
        // Assertions:
        // Total Expense should NOT increase (because usages are excluded)
        $response->assertViewHas('totalExpense', 45812.5); 
        
        // Balance should NOT change
        $response->assertViewHas('balance', 54187.5);

        // ISP Fund should decrease: 21,250 - 10,000 = 11,250
        $response->assertViewHas('totalIspShare', 11250);

        // Tool Fund should decrease: 9,562.5 - 5,000 = 4,562.5
        $response->assertViewHas('totalToolFund', 4562.5);

        // 4. Check Profit & Loss Report
        $response = $this->get(route('finance.profit_loss'));
        $response->assertOk();
        
        // Operating Expenses should be 0 (Usages should not be counted here)
        $response->assertViewHas('operatingExpenses', 0);
        
        // COGS should include Allocations
        // 15,000 (Coord) + 21,250 (ISP) + 9,562.5 (Tool) = 45,812.5
        $response->assertViewHas('totalCOGS', 45812.5);
        
        // Net Profit should match Net Balance
        $response->assertViewHas('netProfit', 54187.5);
    }
}
