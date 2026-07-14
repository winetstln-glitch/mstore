<?php

namespace App\Services\Atk;

use App\DTO\CashImpactData;
use App\Models\AtkCashMovement;
use App\Models\Cash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AtkCashService
{
    public function getLockedMainCash(): Cash
    {
        return Cash::query()
            ->where('name', 'Kas Utama')
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function validateBalance(Cash $cash, int|float $netCashChange): void
    {
        $balanceAfter = (float) $cash->balance + $netCashChange;
        if ($balanceAfter < 0) {
            throw ValidationException::withMessages([
                'cash' => 'Saldo Kas Utama tidak mencukupi untuk transaksi ini.',
            ]);
        }
    }

    public function recordMovement(
        Cash $cash,
        array $movementData
    ): AtkCashMovement {
        $movementData = array_merge([
            'cash_id' => $cash->id,
            'occurred_at' => now(),
            'balance_before' => $cash->balance,
            'balance_after' => $cash->balance,
        ], $movementData);

        // Use firstOrCreate to ensure idempotency
        $movement = AtkCashMovement::firstOrCreate(
            ['idempotency_key' => $movementData['idempotency_key']],
            $movementData
        );

        // Update cash balance projection only if movement was just created
        if ($movement->wasRecentlyCreated) {
            $this->applyProjection($cash, $movementData['balance_after']);
        }

        return $movement;
    }

    public function applyProjection(Cash $cash, int|float $balanceAfter): void
    {
        $cash->update(['balance' => $balanceAfter]);
    }

    public function reverseMovement(
        AtkCashMovement $originalMovement,
        int $userId
    ): AtkCashMovement {
        if ($originalMovement->reversal()->exists() || $originalMovement->reversed_at !== null) {
            throw new \Exception('Movement already reversed.');
        }

        $cash = $this->getLockedMainCash();
        $balanceBefore = $cash->balance;
        $netChange = $originalMovement->direction === 'in' 
            ? -$originalMovement->amount 
            : $originalMovement->amount;
        $balanceAfter = $balanceBefore + $netChange;

        $this->validateBalance($cash, $netChange);

        DB::beginTransaction();

        try {
            $reversalMovement = AtkCashMovement::create([
                'cash_id' => $cash->id,
                'atk_transaction_id' => $originalMovement->atk_transaction_id,
                'movement_type' => 'reversal',
                'direction' => $originalMovement->direction === 'in' ? 'out' : 'in',
                'amount' => $originalMovement->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => $originalMovement->reference_type,
                'reference_id' => $originalMovement->reference_id,
                'idempotency_key' => "atk-cash-reversal:{$originalMovement->id}",
                'description' => "Reversal of movement #{$originalMovement->id}: {$originalMovement->description}",
                'occurred_at' => now(),
                'created_by' => $userId,
                'reversal_of_id' => $originalMovement->id,
            ]);

            $this->applyProjection($cash, $balanceAfter);

            $originalMovement->update([
                'reversed_at' => now(),
                'reversed_by' => $userId,
            ]);

            DB::commit();

            return $reversalMovement;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function reconcileBalance(Cash $cash): array
    {
        // Recalculate balance from movements
        $totalIn = AtkCashMovement::where('cash_id', $cash->id)
            ->where('direction', 'in')
            ->whereNull('reversed_at')
            ->sum('amount');
            
        $totalOut = AtkCashMovement::where('cash_id', $cash->id)
            ->where('direction', 'out')
            ->whereNull('reversed_at')
            ->sum('amount');

        $calculatedBalance = $totalIn - $totalOut;
        $isMatching = abs($calculatedBalance == $cash->balance);

        return [
            'calculated_balance' => $calculatedBalance,
            'current_balance' => $cash->balance,
            'is_matching' => $isMatching,
            'difference' => $calculatedBalance - $cash->balance,
        ];
    }
}
