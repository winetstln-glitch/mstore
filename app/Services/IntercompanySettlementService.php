<?php

namespace App\Services;

use App\Models\IntercompanyTransaction;
use Illuminate\Support\Facades\DB;

class IntercompanySettlementService
{
    public function settleIntercompanyTransaction(int $ictId): IntercompanyTransaction
    {
        return DB::transaction(function () use ($ictId) {
            $ict = IntercompanyTransaction::findOrFail($ictId);

            if ($ict->status !== 'matched') {
                throw new \Exception('Transaction must be matched first before settlement');
            }

            $ict->update([
                'status' => 'settled',
                'settled_at' => now(),
            ]);

            return $ict;
        });
    }

    public function autoSettleMatchedTransactions(): void
    {
        $matchedIcts = IntercompanyTransaction::where('status', 'matched')->get();

        foreach ($matchedIcts as $ict) {
            $this->settleIntercompanyTransaction($ict->id);
        }
    }
}