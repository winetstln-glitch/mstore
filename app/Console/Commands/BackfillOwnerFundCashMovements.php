<?php

namespace App\Console\Commands;

use App\Models\AtkCashMovement;
use App\Models\Cash;
use App\Models\OwnerFund;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillOwnerFundCashMovements extends Command
{
    protected $signature = 'owner-fund:backfill-cash-movements';
    protected $description = 'Backfill missing AtkCashMovement records for existing OwnerFund transactions';

    public function handle()
    {
        $this->info('Starting backfill process...');

        $ownerFunds = OwnerFund::orderBy('id')->get();
        $cash = Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
        $currentCashBalance = $cash->balance;

        foreach ($ownerFunds as $fund) {
            // Check if movement already exists
            $existingMovement = AtkCashMovement::where('idempotency_key', "owner-fund:{$fund->id}")->first();

            if ($existingMovement) {
                $this->line("Movement already exists for OwnerFund {$fund->id} ({$fund->transaction_code}), skipping.");
                continue;
            }

            // Calculate balance before and after
            $prevFund = OwnerFund::where('id', '<', $fund->id)->orderBy('id', 'desc')->first();
            $balanceBefore = $prevFund ? $this->calculateCashBalanceBefore($prevFund) : 0;

            if ($fund->type === 'loan') {
                $balanceAfter = $balanceBefore + $fund->amount;
                $direction = 'in';
                $movementType = 'owner_fund_loan';
            } else {
                $balanceAfter = $balanceBefore - $fund->amount;
                $direction = 'out';
                $movementType = 'owner_fund_repayment';
            }

            try {
                DB::beginTransaction();

                // Create the movement
                AtkCashMovement::create([
                    'cash_id' => $cash->id,
                    'atk_transaction_id' => null,
                    'movement_type' => $movementType,
                    'direction' => $direction,
                    'amount' => $fund->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'idempotency_key' => "owner-fund:{$fund->id}",
                    'description' => $fund->type === 'loan'
                        ? 'Tambah Dana Talangan - ' . $fund->transaction_code
                        : 'Pengembalian Dana Talangan - ' . $fund->transaction_code,
                    'reference_type' => OwnerFund::class,
                    'reference_id' => $fund->id,
                    'occurred_at' => $fund->transaction_date,
                    'created_by' => $fund->created_by,
                ]);

                DB::commit();
                $this->info("Created movement for OwnerFund {$fund->id} ({$fund->transaction_code}).");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to create movement for OwnerFund {$fund->id}: " . $e->getMessage());
            }
        }

        $this->info('Backfill process completed!');
    }

    private function calculateCashBalanceBefore(OwnerFund $beforeFund)
    {
        // Calculate the cash balance as it was before this fund transaction
        $totalLoan = OwnerFund::where('id', '<=', $beforeFund->id)->where('type', 'loan')->sum('amount');
        $totalRepayment = OwnerFund::where('id', '<=', $beforeFund->id)->where('type', 'repayment')->sum('amount');
        
        // We also need to consider initial cash balance, but since we don't track that,
        // we'll calculate based on the fund's own balance logic
        return $beforeFund->balance;
    }
}