<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixReversedAtkTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-reversed-atk-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing reversed ATK transactions...');
        
        $cashService = app(\App\Services\Atk\AtkCashService::class);
        
        $transactions = \App\Models\AtkTransaction::with(['cashMovements'])
            ->where(function ($q) {
                $q->where('status', 'reversed')
                    ->orWhereNotNull('reversed_at');
            })
            ->get();
        
        $this->info('Found ' . $transactions->count() . ' reversed transactions.');
        
        foreach ($transactions as $transaction) {
            $this->info('Processing transaction #' . $transaction->id . ' (' . $transaction->transaction_number . ')');
            
            // Reverse cash movements
            foreach ($transaction->cashMovements as $movement) {
                if ($movement->reversed_at === null) {
                    $this->info('  - Reversing cash movement #' . $movement->id);
                    try {
                        $cashService->reverseMovement($movement, 1); // Use admin user (id 1)
                    } catch (\Exception $e) {
                        $this->error('    Error reversing cash movement: ' . $e->getMessage());
                    }
                }
            }
            
            // Reverse float transactions
            $floatTrans = \App\Models\AtkFloatTransaction::where('reference_type', 'atk_transaction')
                ->where('reference_id', $transaction->id)
                ->whereNull('reversed_at')
                ->get();
            
            foreach ($floatTrans as $ft) {
                $this->info('  - Reversing float transaction #' . $ft->id);
                $floatAccount = $ft->floatAccount;
                if ($floatAccount) {
                    $oppositeType = $ft->transaction_type === 'deposit' ? 'withdrawal' : 'deposit';
                    $newBalance = $ft->transaction_type === 'deposit' 
                        ? $floatAccount->current_balance - $ft->amount 
                        : $floatAccount->current_balance + $ft->amount;

                    $floatAccount->update(['current_balance' => $newBalance]);

                    \App\Models\AtkFloatTransaction::create([
                        'atk_float_account_id' => $floatAccount->id,
                        'transaction_type' => $oppositeType,
                        'amount' => $ft->amount,
                        'balance_before' => $floatAccount->current_balance - ($oppositeType === 'deposit' ? $ft->amount : -$ft->amount),
                        'balance_after' => $newBalance,
                        'description' => 'Pembatalan - ' . $ft->description,
                        'reference_type' => 'atk_float_transaction',
                        'reference_id' => $ft->id,
                        'created_by' => 1,
                    ]);

                    $ft->update(['reversed_at' => now(), 'reversed_by' => 1]);
                }
            }
        }
        
        $this->info('Done!');
    }
}
