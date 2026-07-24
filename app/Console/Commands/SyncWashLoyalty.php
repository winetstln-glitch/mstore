<?php

namespace App\Console\Commands;

use App\Models\WashLoyaltyCounter;
use App\Models\WashRewardVoucher;
use App\Models\WashTransaction;
use App\Services\Wash\WashLoyaltyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncWashLoyalty extends Command
{
    protected $signature = 'wash:sync-loyalty {--plate= : Filter by specific plate number}';

    protected $description = 'Sync wash loyalty counters from old transactions';

    public function handle()
    {
        $this->info('Starting wash loyalty sync...');

        $plateFilter = $this->option('plate');
        $loyaltyService = app(WashLoyaltyService::class);

        $query = WashTransaction::query()
            ->whereIn('status', ['lunas', 'posted'])
            ->where('total_amount', '>', 0)
            ->whereHas('items', function ($q) {
                $q->whereHas('service', function ($sq) {
                    $sq->where('vehicle_type', '!=', 'coffee');
                });
            })
            ->orderBy('id');

        if ($plateFilter) {
            $normalizedPlate = $loyaltyService->normalizePlate($plateFilter);
            $query->where(function ($q) use ($normalizedPlate, $plateFilter) {
                $q->where('vehicle_plate', 'like', "%{$plateFilter}%")
                    ->orWhere(DB::raw("UPPER(REGEXP_REPLACE(vehicle_plate, '[^A-Za-z0-9]', ''))"), $normalizedPlate);
            });
        }

        $transactions = $query->get();
        $this->info("Found {$transactions->count()} valid transactions to process.");

        $grouped = [];
        foreach ($transactions as $transaction) {
            $plate = $loyaltyService->normalizePlate($transaction->vehicle_plate);
            if ($plate === '') {
                continue;
            }
            $grouped[$plate][] = $transaction;
        }

        $this->info("Grouped into " . count($grouped) . " unique plates.");

        foreach ($grouped as $plate => $trans) {
            $this->info("\nProcessing plate: {$plate} (" . count($trans) . " transactions)");
            $counter = WashLoyaltyCounter::query()->firstOrCreate(
                ['vehicle_plate' => $plate],
                [
                    'wash_customer_id' => $trans[0]->wash_customer_id,
                    'wash_member_id' => $trans[0]->wash_member_id,
                    'cycle_paid_count' => 0,
                    'lifetime_paid_count' => 0,
                ]
            );

            $currentCycle = 0;
            $totalLifetime = 0;
            $vouchersCreated = 0;
            foreach ($trans as $trx) {
                $currentCycle++;
                $totalLifetime++;
                $this->line("  - Trx #{$trx->id}: Cycle count = {$currentCycle}");

                if ($currentCycle >= $loyaltyService->target()) {
                    $currentCycle = 0;
                    $this->line("    → Reached target, cycle reset");

                    // Cek apakah voucher untuk transaksi ini sudah ada?
                    $existingVoucher = WashRewardVoucher::query()
                        ->where('vehicle_plate', $plate)
                        ->where('meta->issued_from_transaction_id', $trx->id)
                        ->first();

                    if (!$existingVoucher) {
                        // Buat voucher baru!
                        $code = strtoupper(Str::random(8));
                        $rewardType = 'free_wash';
                        $firstWashItem = $trx->items()
                            ->whereHas('service', function ($q) {
                                $q->where('vehicle_type', '!=', 'coffee');
                            })
                            ->orderBy('id')
                            ->first();

                        if ($firstWashItem instanceof \App\Models\WashTransactionItem) {
                            $vt = strtolower((string) ($firstWashItem->service?->vehicle_type ?? ''));
                            if ($vt === 'car') {
                                $rewardType = 'free_wash_car';
                            } elseif ($vt === 'motor') {
                                $rewardType = 'free_wash_motor';
                            }
                        }

                        $voucher = WashRewardVoucher::create([
                            'code' => $code,
                            'wash_loyalty_counter_id' => $counter->id,
                            'wash_customer_id' => $trx->wash_customer_id,
                            'wash_member_id' => $trx->wash_member_id,
                            'vehicle_plate' => $plate,
                            'reward_type' => $rewardType,
                            'status' => 'available',
                            'issued_at' => $trx->created_at,
                            'expires_at' => $trx->created_at->addDays($loyaltyService->voucherExpiryDays()),
                            'meta' => [
                                'issued_from_transaction_id' => $trx->id,
                                'target' => $loyaltyService->target(),
                            ],
                        ]);
                        $vouchersCreated++;
                        $this->line("    → ✅ Created voucher: {$voucher->code}");
                    } else {
                        $this->line("    → ℹ️ Voucher already exists: {$existingVoucher->code}");
                    }
                }
            }

            $counter->update([
                'cycle_paid_count' => $currentCycle,
                'lifetime_paid_count' => $totalLifetime,
                'last_paid_transaction_id' => end($trans)->id,
                'last_paid_at' => end($trans)->created_at,
            ]);

            $this->info("  → Updated: Cycle count = {$currentCycle}, Lifetime = {$totalLifetime}, Vouchers created: {$vouchersCreated}");
        }

        $this->info("\n✅ Sync wash loyalty completed!");
        return 0;
    }
}
