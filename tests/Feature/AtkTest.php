<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AtkProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtkTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create user with permissions
        $this->user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin', 'label' => 'Administrator']);
        $this->user->role_id = $role->id;
        $this->user->save();
        
        // Ensure permissions exist
        $permissions = ['atk.view', 'atk.manage', 'atk.pos', 'atk.report'];
        foreach ($permissions as $p) {
            $perm = Permission::firstOrCreate(['name' => $p, 'label' => $p, 'group' => 'ATK']);
            $role->permissions()->syncWithoutDetaching([$perm->id]);
        }
    }

    public function test_can_view_atk_dashboard()
    {
        $response = $this->actingAs($this->user)->get(route('atk.dashboard'));
        $response->assertStatus(200);
    }

    public function test_can_create_product()
    {
        $response = $this->actingAs($this->user)->post(route('atk.products.store'), [
            'code' => 'TEST001',
            'name' => 'Test Product',
            'stock' => 100,
            'unit' => 'pcs',
            'buy_price' => 5000,
            'sell_price_retail' => 6000,
            'sell_price_wholesale' => 5500,
        ]);

        $response->assertRedirect(route('atk.products.index'));
        $this->assertDatabaseHas('atk_products', ['code' => 'TEST001']);
    }

    public function test_can_perform_transaction()
    {
        $product = AtkProduct::create([
            'code' => 'TEST002',
            'name' => 'Test Product 2',
            'stock' => 100,
            'unit' => 'pcs',
            'buy_price' => 5000,
            'sell_price_retail' => 6000,
            'sell_price_wholesale' => 5500,
        ]);

        $response = $this->actingAs($this->user)->post(route('atk.pos.store'), [
            'items' => [
                [
                    'id' => $product->id,
                    'quantity' => 2,
                    'price_type' => 'retail'
                ]
            ],
            'payment_method' => 'cash',
            'amount_paid' => 20000,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('atk_transactions', ['total_amount' => 12000]); // 6000 * 2
        
        $product->refresh();
        $this->assertEquals(98, $product->stock);
    }
}
