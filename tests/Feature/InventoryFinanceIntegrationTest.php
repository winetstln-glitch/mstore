<?php

namespace Tests\Feature;

use App\Models\Coordinator;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Region;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InventoryFinanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pickup_with_coordinator_creates_pengeluaran_pengurus_transaction()
    {
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $region = Region::create(['name' => 'Test Region']);
        $coordinator = Coordinator::create([
            'name' => 'Test Coordinator',
            'user_id' => $user->id,
            'region_id' => $region->id,
            'address' => 'Address',
        ]);

        $item = InventoryItem::create([
            'name' => 'ONU',
            'description' => 'Test Item',
            'unit' => 'pcs',
            'stock' => 10,
            'price' => 250000,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('inventory.store-pickup'), [
            'inventory_item_id' => $item->id,
            'quantity' => 2,
            'usage' => 'New Installation',
            'proof_image' => UploadedFile::fake()->create('proof.jpg'),
            'description' => 'Test pickup',
            'coordinator_id' => $coordinator->id,
        ]);

        $response->assertRedirect(route('inventory.index'));

        $inventoryTransaction = InventoryTransaction::first();
        $this->assertNotNull($inventoryTransaction);

        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'category' => 'Pengeluaran Pengurus',
            'coordinator_id' => $coordinator->id,
            'reference_number' => 'INV-OUT-' . $inventoryTransaction->id,
            'amount' => 500000,
        ]);
    }

    public function test_update_pickup_updates_pengeluaran_pengurus_amount()
    {
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $region = Region::create(['name' => 'Test Region']);
        $coordinator = Coordinator::create([
            'name' => 'Test Coordinator',
            'user_id' => $user->id,
            'region_id' => $region->id,
            'address' => 'Address',
        ]);

        $item = InventoryItem::create([
            'name' => 'ONU',
            'description' => 'Test Item',
            'unit' => 'pcs',
            'stock' => 10,
            'price' => 100000,
        ]);

        $this->actingAs($user);

        $this->post(route('inventory.store-pickup'), [
            'inventory_item_id' => $item->id,
            'quantity' => 1,
            'usage' => 'New Installation',
            'proof_image' => UploadedFile::fake()->create('proof.jpg'),
            'description' => 'First pickup',
            'coordinator_id' => $coordinator->id,
        ]);

        $inventoryTransaction = InventoryTransaction::first();

        $this->put(route('inventory.pickup.update', $inventoryTransaction), [
            'quantity' => 3,
            'description' => 'Updated pickup',
        ]);

        $this->assertDatabaseHas('transactions', [
            'reference_number' => 'INV-OUT-' . $inventoryTransaction->id,
            'amount' => 300000,
        ]);
    }

    public function test_destroy_pickup_deletes_related_pengeluaran_pengurus()
    {
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $region = Region::create(['name' => 'Test Region']);
        $coordinator = Coordinator::create([
            'name' => 'Test Coordinator',
            'user_id' => $user->id,
            'region_id' => $region->id,
            'address' => 'Address',
        ]);

        $item = InventoryItem::create([
            'name' => 'ONU',
            'description' => 'Test Item',
            'unit' => 'pcs',
            'stock' => 10,
            'price' => 150000,
        ]);

        $this->actingAs($user);

        $this->post(route('inventory.store-pickup'), [
            'inventory_item_id' => $item->id,
            'quantity' => 2,
            'usage' => 'New Installation',
            'proof_image' => UploadedFile::fake()->create('proof.jpg'),
            'description' => 'Pickup to delete',
            'coordinator_id' => $coordinator->id,
        ]);

        $inventoryTransaction = InventoryTransaction::first();

        $this->assertDatabaseHas('transactions', [
            'reference_number' => 'INV-OUT-' . $inventoryTransaction->id,
            'category' => 'Pengeluaran Pengurus',
        ]);

        $this->delete(route('inventory.pickup.destroy', $inventoryTransaction));

        $this->assertDatabaseMissing('transactions', [
            'reference_number' => 'INV-OUT-' . $inventoryTransaction->id,
        ]);
    }

    public function test_create_item_with_stock_creates_pembelian_alat_expense()
    {
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);

        $response = $this->post(route('inventory.store'), [
            'name' => 'ONU',
            'unit' => 'pcs',
            'stock' => 5,
            'price' => 200000,
            'description' => 'Initial stock',
        ]);

        $response->assertStatus(302);

        $item = InventoryItem::first();

        $this->assertNotNull($item);

        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'category' => 'Pembelian Alat',
            'reference_number' => 'INV-IN-' . $item->id,
            'amount' => 1000000,
        ]);
    }

    public function test_update_item_increasing_stock_creates_pembelian_alat_expense_for_difference()
    {
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $item = InventoryItem::create([
            'name' => 'ONU',
            'description' => 'Test Item',
            'unit' => 'pcs',
            'stock' => 2,
            'price' => 150000,
        ]);

        $this->actingAs($user);

        $response = $this->put(route('inventory.update', $item), [
            'name' => $item->name,
            'unit' => $item->unit,
            'stock' => 5,
            'price' => $item->price,
            'description' => 'Tambah stok',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'category' => 'Pembelian Alat',
            'amount' => 3 * 150000,
        ]);
    }
}
