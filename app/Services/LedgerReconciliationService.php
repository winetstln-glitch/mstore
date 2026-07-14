<?php

namespace App\Services;

use App\Models\GeneralTransaction;
use App\Models\Journal;
use App\Models\ReconciliationReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerReconciliationService
{
    public function reconcileDay(Carbon $date, ?int $businessUnitId = null): ReconciliationReport
    {
        return DB::transaction(function () use ($date, $businessUnitId) {
            $details = [];
            $totalTransactionAmount = 0;
            $totalJournalAmount = 0;
            $mismatches = [];

            $transactions = GeneralTransaction::whereDate('created_at', $date)
                ->when($businessUnitId, fn($q, $id) => $q->where('business_unit_id', $id))
                ->where('status', 'posted')
                ->get();

            foreach ($transactions as $tx) {
                $totalTransactionAmount += $tx->amount;

                $journal = Journal::where('source_type', GeneralTransaction::class)
                    ->where('source_id', $tx->id)
                    ->first();

                if (!$journal) {
                    $mismatches[] = [
                        'type' => 'missing_journal',
                        'transaction_id' => $tx->id,
                        'transaction_code' => $tx->transaction_code,
                    ];
                    continue;
                }

                $journalTotal = $journal->entries->sum('debit');
                if (abs($journalTotal - $tx->amount) > 0.01) {
                    $mismatches[] = [
                        'type' => 'amount_mismatch',
                        'transaction_id' => $tx->id,
                        'transaction_amount' => $tx->amount,
                        'journal_amount' => $journalTotal,
                    ];
                }
            }

            $totalTransactions = $transactions->count();
            $totalJournalEntries = DB::table('journal_entries')
                ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                ->whereDate('journals.created_at', $date)
                ->when($businessUnitId, function ($q) use ($businessUnitId) {
                    $q->whereIn('journals.id', function ($subQ) use ($businessUnitId) {
                        $subQ->select('journals.id')
                            ->from('journals')
                            ->where('source_type', GeneralTransaction::class)
                            ->whereIn('source_id', function ($subSubQ) use ($businessUnitId) {
                                $subSubQ->select('id')
                                    ->from('general_transactions')
                                    ->where('business_unit_id', $businessUnitId);
                            });
                    });
                })
                ->count();

            $difference = $totalTransactionAmount - $totalJournalAmount;
            $status = 'balanced';

            if (count($mismatches) > 0 || abs($difference) > 0.01) {
                $status = count($mismatches) > 10 || abs($difference) > 1000000 ? 'critical' : 'mismatch';
            }

            $details = [
                'mismatches' => $mismatches,
                'total_transaction_amount' => $totalTransactionAmount,
                'total_journal_amount' => $totalJournalAmount,
            ];

            return ReconciliationReport::updateOrCreate(
                [
                    'date' => $date->toDateString(),
                    'business_unit_id' => $businessUnitId,
                ],
                [
                    'total_transactions' => $totalTransactions,
                    'total_journal_entries' => $totalJournalEntries,
                    'difference' => $difference,
                    'status' => $status,
                    'details_json' => $details,
                ]
            );
        });
    }
}