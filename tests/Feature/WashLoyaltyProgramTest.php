<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\WashLoyaltyCounter;
use App\Models\WashRewardRedemption;
use App\Models\WashRewardVoucher;
use App\Models\WashService;
use App\Models\WashTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WashLoyaltyProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_counter_increments_and_creates_voucher_on_10th_paid_transaction_then_resets(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $service = WashService::create([
            'name' => 'Cuci Mobil',
            'vehicle_type' => 'car',
            'price' => 50000,
            'is_active' => true,
        ]);

        $plate = 'B 1234 XYZ';
        $normalizedPlate = 'B1234XYZ';

        $this->actingAs($admin);

        for ($i = 1; $i <= 10; $i++) {
            $res = $this->postJson(route('wash.transactions.store'), [
                'items' => [
                    ['id' => $service->id, 'quantity' => 1],
                ],
                'payment_method' => 'cash',
                'cash_amount' => 50000,
                'customer_name' => 'Budi',
                'customer_phone' => '081234567890',
                'vehicle_plate' => $plate,
                'vehicle_brand' => 'Toyota',
            ]);

            $this->assertSame(200, $res->status(), $res->getContent());
            $res->assertJson(['success' => true]);
        }

        $counter = WashLoyaltyCounter::query()->where('vehicle_plate', $normalizedPlate)->first();
        $this->assertNotNull($counter);
        $this->assertSame(0, (int) $counter->cycle_paid_count);
        $this->assertSame(10, (int) $counter->lifetime_paid_count);

        $voucher = WashRewardVoucher::query()->where('vehicle_plate', $normalizedPlate)->first();
        $this->assertNotNull($voucher);
        $this->assertSame('GW-FREE-00001', $voucher->code);
        $this->assertSame('available', $voucher->status);
    }

    public function test_voucher_can_be_used_once_and_transaction_total_becomes_zero(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $service = WashService::create([
            'name' => 'Cuci Mobil',
            'vehicle_type' => 'car',
            'price' => 50000,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        for ($i = 1; $i <= 10; $i++) {
            $this->postJson(route('wash.transactions.store'), [
                'items' => [
                    ['id' => $service->id, 'quantity' => 1],
                ],
                'payment_method' => 'cash',
                'cash_amount' => 50000,
                'customer_name' => 'Budi',
                'customer_phone' => '081234567890',
                'vehicle_plate' => 'B1234XYZ',
                'vehicle_brand' => 'Toyota',
            ])->assertOk();
        }

        $voucher = WashRewardVoucher::query()->where('code', 'GW-FREE-00001')->first();
        $this->assertNotNull($voucher);

        $redeemRes = $this->postJson(route('wash.transactions.store'), [
            'items' => [
                ['id' => $service->id, 'quantity' => 1],
            ],
            'payment_method' => 'cash',
            'cash_amount' => 0,
            'customer_name' => 'Budi',
            'customer_phone' => '081234567890',
            'vehicle_plate' => 'B1234XYZ',
            'vehicle_brand' => 'Toyota',
            'use_voucher' => true,
            'voucher_code' => 'GW-FREE-00001',
        ]);

        $redeemRes->assertOk()
            ->assertJson([
                'success' => true,
                'discount_type' => 'reward_voucher',
                'redeemed_voucher_code' => 'GW-FREE-00001',
            ]);

        $voucher->refresh();
        $this->assertSame('used', $voucher->status);
        $this->assertNotNull($voucher->used_at);

        $trx = WashTransaction::query()->find((int) $redeemRes->json('transaction_id'));
        $this->assertNotNull($trx);
        $this->assertSame(0.0, (float) $trx->total_amount);

        $this->assertDatabaseHas('wash_reward_redemptions', [
            'wash_reward_voucher_id' => $voucher->id,
            'wash_transaction_id' => $trx->id,
        ]);

        $counter = WashLoyaltyCounter::query()->where('vehicle_plate', 'B1234XYZ')->first();
        $this->assertNotNull($counter);
        $this->assertSame(0, (int) $counter->cycle_paid_count);
        $this->assertSame(10, (int) $counter->lifetime_paid_count);

        $again = $this->postJson(route('wash.transactions.store'), [
            'items' => [
                ['id' => $service->id, 'quantity' => 1],
            ],
            'payment_method' => 'cash',
            'cash_amount' => 0,
            'customer_name' => 'Budi',
            'customer_phone' => '081234567890',
            'vehicle_plate' => 'B1234XYZ',
            'vehicle_brand' => 'Toyota',
            'use_voucher' => true,
            'voucher_code' => 'GW-FREE-00001',
        ]);

        $again->assertStatus(400);
    }

    public function test_loyalty_cycle_repeats_and_generates_second_voucher(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $service = WashService::create([
            'name' => 'Cuci Mobil',
            'vehicle_type' => 'car',
            'price' => 50000,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        for ($i = 1; $i <= 20; $i++) {
            $this->postJson(route('wash.transactions.store'), [
                'items' => [
                    ['id' => $service->id, 'quantity' => 1],
                ],
                'payment_method' => 'cash',
                'cash_amount' => 50000,
                'customer_name' => 'Budi',
                'customer_phone' => '081234567890',
                'vehicle_plate' => 'B1234XYZ',
                'vehicle_brand' => 'Toyota',
            ])->assertOk();
        }

        $this->assertDatabaseHas('wash_reward_vouchers', [
            'code' => 'GW-FREE-00001',
            'status' => 'available',
        ]);

        $this->assertDatabaseHas('wash_reward_vouchers', [
            'code' => 'GW-FREE-00002',
            'status' => 'available',
        ]);
    }
}
