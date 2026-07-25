<?php

namespace App\Console\Commands;

use App\Models\WashLoyaltyCounter;
use App\Models\WashRewardVoucher;
use App\Models\WashTransaction;
use App\Services\Wash\WashLoyaltyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
                    ->orWhereRaw(
                        "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(vehicle_plate, ''), ' ', ''), '-', ''), '.', ''), '/', '')) LIKE ?",
                        ['%' . $normalizedPlate . '%']
                    );
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

            // Reset counter for accurate sync (since we're reprocessing all transactions)
            $counter = WashLoyaltyCounter::query()->updateOrCreate(
                ['vehicle_plate' => $plate],
                [
                    'wash_customer_id' => $trans[0]->wash_customer_id,
                    'wash_member_id' => $trans[0]->wash_member_id,
                    'cycle_paid_count' => 0,
                    'lifetime_paid_count' => 0,
                    'last_paid_transaction_id' => null,
                    'last_paid_at' => null,
                ]
            );

            // Also delete any vouchers that were issued from these transactions, since we're re-syncing
            $trxIds = collect($trans)->pluck('id');
            WashRewardVoucher::query()
                ->where('vehicle_plate', $plate)
                ->where(function ($q) use ($trxIds) {
                    $q->whereIn('meta->issued_from_transaction_id', $trxIds);
                })
                ->delete();

            // Process each transaction using the existing service method to stay consistent
            // Force recount since we've reset everything
            foreach ($trans as $trx) {
                $this->line("  - Trx #{$trx->id} ({$trx->transaction_number})");

                // Use incrementOnPaidTransaction with forceCount=true to ensure it's counted
                $result = $loyaltyService->incrementOnPaidTransaction($trx, true);

                if ($result['created_voucher']) {
                    $this->line("    → ✅ Created voucher: {$result['created_voucher']->code}");
                }
            }

            $this->info("  → Updated counter: Cycle count = {$counter->fresh()->cycle_paid_count}, Lifetime = {$counter->fresh()->lifetime_paid_count}");
        }

        $this->info("\n✅ Sync wash loyalty completed!");
        return 0;
    }
}
