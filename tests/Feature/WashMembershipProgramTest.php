<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\WashMember;
use App\Models\WashMemberCard;
use App\Models\WashMemberLevel;
use App\Models\WashMemberVehicle;
use App\Models\WashService;
use App\Models\WashTransaction;
use App\Services\Wash\WashMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WashMembershipProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_is_auto_created_with_vehicle_and_card_after_paid_transaction(): void
    {
        [$admin, $service] = $this->makeAdminAndService();

        $this->actingAs($admin)
            ->postJson(route('wash.transactions.store'), [
                'items' => [
                    ['id' => $service->id, 'quantity' => 1],
                ],
                'payment_method' => 'cash',
                'cash_amount' => 50000,
                'customer_name' => 'Budi Santoso',
                'customer_phone' => '081234567890',
                'vehicle_plate' => 'B 1234 XYZ',
                'vehicle_brand' => 'Toyota',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $member = WashMember::query()->where('whatsapp', '6281234567890')->first();
        $this->assertNotNull($member);
        $this->assertSame('Budi Santoso', $member->name);
        $this->assertSame('active', $member->status);

        $vehicle = WashMemberVehicle::query()->where('wash_member_id', $member->id)->first();
        $this->assertNotNull($vehicle);
        $this->assertSame('B1234XYZ', $vehicle->vehicle_plate);

        $card = WashMemberCard::query()->where('wash_member_id', $member->id)->first();
        $this->assertNotNull($card);
        $this->assertSame($member->member_number, $card->card_number);
    }

    public function test_member_level_upgrades_to_silver_after_10_paid_transactions(): void
    {
        [$admin, $service] = $this->makeAdminAndService();

        $this->actingAs($admin);

        for ($i = 1; $i <= 10; $i++) {
            $this->postJson(route('wash.transactions.store'), [
                'items' => [
                    ['id' => $service->id, 'quantity' => 1],
                ],
                'payment_method' => 'cash',
                'cash_amount' => 50000,
                'customer_name' => 'Budi Santoso',
                'customer_phone' => '081234567890',
                'vehicle_plate' => 'B1234XYZ',
                'vehicle_brand' => 'Toyota',
            ])->assertOk();
        }

        $member = WashMember::query()->with('level')->where('whatsapp', '6281234567890')->first();
        $this->assertNotNull($member);
        $this->assertSame(10, (int) $member->total_transactions);
        $this->assertSame('silver', $member->level?->code);
    }

    public function test_member_discount_applies_based_on_current_level(): void
    {
        [$admin, $service] = $this->makeAdminAndService();

        $membership = app(WashMembershipService::class);
        $member = $membership->ensureMember('Budi Santoso', '081234567890', 'B1234XYZ', 'Toyota');
        $this->assertNotNull($member);

        $goldLevel = WashMemberLevel::query()->where('code', 'gold')->first();
        $this->assertNotNull($goldLevel);

        $member->forceFill([
            'wash_member_level_id' => $goldLevel->id,
            'total_transactions' => 25,
            'total_visits' => 25,
            'total_spending' => 1250000,
        ])->save();

        $response = $this->actingAs($admin)
            ->postJson(route('wash.transactions.store'), [
                'items' => [
                    ['id' => $service->id, 'quantity' => 1],
                ],
                'payment_method' => 'cash',
                'cash_amount' => 50000,
                'customer_name' => 'Budi Santoso',
                'customer_phone' => '081234567890',
                'vehicle_plate' => 'B1234XYZ',
                'vehicle_brand' => 'Toyota',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'discount_type' => 'member_discount',
                'member_discount_percent' => 5.0,
            ]);

        $transaction = WashTransaction::query()->find((int) $response->json('transaction_id'));
        $this->assertNotNull($transaction);
        $this->assertSame(2500.0, (float) $transaction->member_discount_amount);
        $this->assertSame(47500.0, (float) $transaction->total_amount);
    }

    public function test_member_card_verification_page_is_publicly_accessible(): void
    {
        $member = app(WashMembershipService::class)->ensureMember('Budi Santoso', '081234567890', 'B1234XYZ', 'Toyota');
        $this->assertNotNull($member);

        $card = WashMemberCard::query()->where('wash_member_id', $member->id)->first();
        $this->assertNotNull($card);

        $this->get(route('wash.members.verify', $card->verification_token))
            ->assertOk()
            ->assertSee($member->member_number)
            ->assertSee($member->name);
    }

    public function test_gold_member_gets_priority_queue_order_above_bronze_queue(): void
    {
        [$admin, $service] = $this->makeAdminAndService();

        $membership = app(WashMembershipService::class);
        $bronzeMember = $membership->ensureMember('Bronze User', '081111111111', 'B1111AAA', 'Toyota');
        $goldMember = $membership->ensureMember('Gold User', '082222222222', 'B2222BBB', 'Honda');

        $this->assertNotNull($bronzeMember);
        $this->assertNotNull($goldMember);

        $goldLevel = WashMemberLevel::query()->where('code', 'gold')->first();
        $this->assertNotNull($goldLevel);

        $goldMember->forceFill([
            'wash_member_level_id' => $goldLevel->id,
            'total_transactions' => 25,
            'total_visits' => 25,
            'total_spending' => 1250000,
        ])->save();

        $this->actingAs($admin);

        $bronzeResponse = $this->postJson(route('wash.transactions.store'), [
            'items' => [
                ['id' => $service->id, 'quantity' => 1],
            ],
            'payment_method' => 'cash',
            'cash_amount' => 50000,
            'customer_name' => 'Bronze User',
            'customer_phone' => '081111111111',
            'vehicle_plate' => 'B1111AAA',
            'vehicle_brand' => 'Toyota',
        ]);

        $bronzeResponse->assertOk()
            ->assertJson([
                'success' => true,
                'queue_priority_label' => 'Bronze Queue',
            ]);

        $goldResponse = $this->postJson(route('wash.transactions.store'), [
            'items' => [
                ['id' => $service->id, 'quantity' => 1],
            ],
            'payment_method' => 'cash',
            'cash_amount' => 50000,
            'customer_name' => 'Gold User',
            'customer_phone' => '082222222222',
            'vehicle_plate' => 'B2222BBB',
            'vehicle_brand' => 'Honda',
        ]);

        $goldResponse->assertOk()
            ->assertJson([
                'success' => true,
                'queue_priority_label' => 'Gold Priority',
                'queue_service_order_today' => 1,
            ]);

        $bronzeTransaction = WashTransaction::query()->find((int) $bronzeResponse->json('transaction_id'));
        $goldTransaction = WashTransaction::query()->find((int) $goldResponse->json('transaction_id'));

        $this->assertNotNull($bronzeTransaction);
        $this->assertNotNull($goldTransaction);
        $this->assertSame('B-001', $bronzeTransaction->queue_display);
        $this->assertSame('G-001', $goldTransaction->queue_display);
        $this->assertSame(2, $bronzeTransaction->queue_service_order_today);
        $this->assertSame(1, $goldTransaction->queue_service_order_today);
    }

    public function test_level_up_discount_becomes_effective_on_next_transaction_not_triggering_transaction(): void
    {
        [$admin, $service] = $this->makeAdminAndService();

        $membership = app(WashMembershipService::class);
        $member = $membership->ensureMember('Silver Candidate', '083333333333', 'B3333CCC', 'Suzuki');
        $this->assertNotNull($member);

        $bronzeLevel = WashMemberLevel::query()->where('code', 'bronze')->first();
        $silverLevel = WashMemberLevel::query()->where('code', 'silver')->first();
        $this->assertNotNull($bronzeLevel);
        $this->assertNotNull($silverLevel);

        $member->forceFill([
            'wash_member_level_id' => $bronzeLevel->id,
            'total_transactions' => 9,
            'total_visits' => 9,
            'total_spending' => 450000,
        ])->save();

        $this->actingAs($admin);

        $triggeringResponse = $this->postJson(route('wash.transactions.store'), [
            'items' => [
                ['id' => $service->id, 'quantity' => 1],
            ],
            'payment_method' => 'cash',
            'cash_amount' => 50000,
            'customer_name' => 'Silver Candidate',
            'customer_phone' => '083333333333',
            'vehicle_plate' => 'B3333CCC',
            'vehicle_brand' => 'Suzuki',
        ]);

        $triggeringResponse->assertOk()
            ->assertJson([
                'success' => true,
                'member_discount_percent' => 0.0,
                'membership_level_upgraded' => true,
                'membership_new_level' => 'silver',
                'membership_new_level_effective_from' => 'next_transaction',
            ]);

        $triggeringTransaction = WashTransaction::query()->find((int) $triggeringResponse->json('transaction_id'));
        $this->assertNotNull($triggeringTransaction);
        $this->assertSame(0.0, (float) $triggeringTransaction->member_discount_amount);
        $this->assertSame(50000.0, (float) $triggeringTransaction->total_amount);

        $member->refresh();
        $this->assertSame('silver', $member->level?->code);
        $this->assertSame(10, (int) $member->total_transactions);

        $nextResponse = $this->postJson(route('wash.transactions.store'), [
            'items' => [
                ['id' => $service->id, 'quantity' => 1],
            ],
            'payment_method' => 'cash',
            'cash_amount' => 50000,
            'customer_name' => 'Silver Candidate',
            'customer_phone' => '083333333333',
            'vehicle_plate' => 'B3333CCC',
            'vehicle_brand' => 'Suzuki',
        ]);

        $nextResponse->assertOk()
            ->assertJson([
                'success' => true,
                'member_discount_percent' => 3.0,
            ]);

        $nextTransaction = WashTransaction::query()->find((int) $nextResponse->json('transaction_id'));
        $this->assertNotNull($nextTransaction);
        $this->assertSame(1500.0, (float) $nextTransaction->member_discount_amount);
        $this->assertSame(48500.0, (float) $nextTransaction->total_amount);
    }

    private function makeAdminAndService(): array
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $service = WashService::create([
            'name' => 'Cuci Mobil',
            'vehicle_type' => 'car',
            'price' => 50000,
            'is_active' => true,
        ]);

        return [$admin, $service];
    }
}
