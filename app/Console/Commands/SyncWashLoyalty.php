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

        // Build looser initial query; we'll do more accurate filtering in PHP
        $query = WashTransaction::query()
            ->with('items.service')
            ->whereIn('status', ['lunas', 'posted'])
            ->where('total_amount', '>', 0)
            ->orderBy('id');

        // NOTE: We intentionally do NOT filter by plate in the SQL query!
        // SQL filtering with LIKE can miss transactions due to formatting/separator differences
        // (e.g. "A 1806 QA" vs "A1806QA"). We do 100% accurate filtering in PHP below instead.

        $allTransactions = $query->get();
        $this->info("Found {$allTransactions->count()} raw transactions (before PHP filtering).");

        // PHP-level filtering: more accurate and handles missing/deleted services
        $validTransactions = collect();
        foreach ($allTransactions as $transaction) {
            $plate = $loyaltyService->normalizePlate($transaction->vehicle_plate);
            if ($plate === '') {
                continue;
            }

            // If user filtered by plate, double-check exact normalized match in PHP
            if ($plateFilter) {
                $targetPlate = $loyaltyService->normalizePlate($plateFilter);
                if ($plate !== $targetPlate) {
                    continue;
                }
            }

            // FIRST: EXCLUDE ANY REDEMPTIONS (free/bonus washes)
            if ($loyaltyService->isRedemptionTransaction($transaction)) {
                continue;
            }

            // Check if this transaction has any NON-coffee items
            // We check both: 1) the service relationship if it exists, OR 2) the stored service name
            $hasNonCoffee = false;
            foreach ($transaction->items as $item) {
                $vehicleType = null;
                $service = $item->service;
                if ($service) {
                    $vehicleType = strtolower((string) ($service->vehicle_type ?? ''));
                }

                $serviceName = strtolower(trim((string) ($item->service_name ?? '')));

                // Determine if this item counts as "non coffee"
                $isCoffee = false;
                if ($vehicleType === 'coffee') {
                    $isCoffee = true;
                } elseif (
                    $serviceName === 'kopi' ||
                    $serviceName === 'caffe' ||
                    $serviceName === 'warkop' ||
                    str_contains($serviceName, 'kopi') ||
                    str_contains($serviceName, 'caffe') ||
                    str_contains($serviceName, 'warkop')
                ) {
                    $isCoffee = true;
                }

                if (!$isCoffee) {
                    $hasNonCoffee = true;
                    break;
                }
            }
            if (!$hasNonCoffee) {
                continue;
            }

            $validTransactions->push($transaction);
        }

        $this->info("After filtering, found {$validTransactions->count()} VALID transactions to process.");

        $grouped = [];
        foreach ($validTransactions as $transaction) {
            $plate = $loyaltyService->normalizePlate($transaction->vehicle_plate);
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
