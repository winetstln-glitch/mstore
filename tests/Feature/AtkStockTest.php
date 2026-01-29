<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AtkProduct;
use App\Models\AtkTransaction;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtkStockTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup permissions using App\Models
        // Ensure permissions exist
        $permManage = Permission::firstOrCreate(['name' => 'atk.manage', 'label' => 'Manage ATK', 'group' => 'ATK']);
        $permView = Permission::firstOrCreate(['name' => 'atk.view', 'label' => 'View ATK', 'group' => 'ATK']);
        
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        
        // Attach permissions manually since it's a BelongsToMany relationship
        $role->permissions()->attach([$permManage->id, $permView->id]);

        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
    }

    public function test_can_create_atk_product()
    {
        $response = $this->actingAs($this->user)->post(route('atk.products.store'), [
            'code' => 'ATK001',
            'name' => 'Pensil 2B',
            'stock' => 10,
            'unit' => 'pcs',
            'buy_price' => 2000,
            'sell_price_retail' => 3000,
            'sell_price_wholesale' => 2500,
        ]);

        $response->assertRedirect(route('atk.products.index'));
        $this->assertDatabaseHas('atk_products', ['code' => 'ATK001', 'stock' => 10]);
    }

    public function test_can_restock_atk_product()
    {
        $product = AtkProduct::create([
            'code' => 'ATK002',
            'name' => 'Buku Tulis',
            'stock' => 50,
            'unit' => 'pcs',
            'buy_price' => 3000,
            'sell_price_retail' => 5000,
            'sell_price_wholesale' => 4500,
        ]);

        $response = $this->actingAs($this->user)->post(route('atk.products.restock', $product), [
            'quantity' => 20,
            'note' => 'Restock from Supplier A',
        ]);

        $response->assertRedirect(route('atk.products.index'));
        
        // Check stock updated
        $this->assertDatabaseHas('atk_products', [
            'id' => $product->id,
            'stock' => 70 // 50 + 20
        ]);

        // Check transaction created
        $this->assertDatabaseHas('atk_transactions', [
            'type' => 'in',
            'notes' => 'Restock from Supplier A'
        ]);
    }

    public function test_pos_transaction_decreases_stock_and_records_payment()
    {
        $product = AtkProduct::create([
            'code' => 'ATK003',
            'name' => 'Pulpen Standard',
            'stock' => 100,
            'unit' => 'pcs',
            'buy_price' => 1500,
            'sell_price_retail' => 2500,
            'sell_price_wholesale' => 2000,
        ]);

        $response = $this->actingAs($this->user)->post(route('atk.pos.store'), [
            'items' => [
                [
                    'id' => $product->id,
                    'quantity' => 5,
                    'price_type' => 'retail'
                ]
            ],
            'customer_name' => 'Budi',
            'amount_paid' => 15000, // Total is 12500 (5 * 2500), paid 15000
            'payment_method' => 'cash',
        ]);

        $response->assertJson(['success' => true]);

        $transaction = AtkTransaction::latest()->first();
        
        // Check stock decreased
        $this->assertDatabaseHas('atk_products', [
            'id' => $product->id,
            'stock' => 95 // 100 - 5
        ]);

        // Check transaction details
        $this->assertDatabaseHas('atk_transactions', [
            'id' => $transaction->id,
            'customer_name' => 'Budi',
            'total_amount' => 12500, // 5 * 2500
            'amount_paid' => 15000,
            'type' => 'out'
        ]);
    }

    public function test_receipt_page_loads_correctly()
    {
        $transaction = AtkTransaction::create([
            'invoice_number' => 'INV-TEST-001',
            'user_id' => $this->user->id,
            'customer_name' => 'Siti',
            'total_amount' => 50000,
            'amount_paid' => 100000,
            'payment_method' => 'cash',
            'type' => 'out'
        ]);

        $response = $this->actingAs($this->user)->get(route('atk.transactions.receipt', $transaction));

        $response->assertStatus(200);
        $response->assertSee('INV-TEST-001');
        $response->assertSee('Siti');
        $response->assertSee('50.000'); // Total
        $response->assertSee('100.000'); // Paid
        $response->assertSee('50.000'); // Change (100k - 50k)
    }
}
