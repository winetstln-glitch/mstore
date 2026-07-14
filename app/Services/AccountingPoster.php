<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Period;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingPoster
{
    public function post(string $journalNo, string $date, string $description, array $lines, ?int $periodId = null, ?string $sourceType = null, ?int $sourceId = null): Journal
    {
        $sumDebit = 0;
        $sumCredit = 0;
        foreach ($lines as $l) {
            $sumDebit += (float) ($l['debit'] ?? 0);
            $sumCredit += (float) ($l['credit'] ?? 0);
        }
        if (round($sumDebit, 2) !== round($sumCredit, 2)) {
            throw new InvalidArgumentException('Unbalanced journal');
        }

        return DB::transaction(function () use ($journalNo, $date, $description, $lines, $periodId, $sourceType, $sourceId) {
            if (! $periodId) {
                $d = \Carbon\Carbon::parse($date);
                $name = $d->format('Y-m');
                $start = $d->copy()->startOfMonth()->toDateString();
                $end = $d->copy()->endOfMonth()->toDateString();
                $period = Period::firstOrCreate(
                    ['name' => $name],
                    ['start_date' => $start, 'end_date' => $end, 'status' => 'open']
                );
                $periodId = $period->id;
            }
            $period = Period::findOrFail($periodId);
            if ($period->status === 'closed') {
                throw new InvalidArgumentException('Cannot post to a closed period');
            }
            // Delete any existing journal with same journal_no first
            Journal::where('journal_no', $journalNo)->delete();
            
            // Create new journal
            $j = Journal::create([
                'journal_no' => $journalNo,
                'date' => $date,
                'description' => $description,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'period_id' => $periodId,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'status' => 'posted',
            ]);
            foreach ($lines as $l) {
                JournalEntry::create([
                    'journal_id' => $j->id,
                    'account_id' => $l['account_id'],
                    'debit' => $l['debit'] ?? 0,
                    'credit' => $l['credit'] ?? 0,
                    'memo' => $l['memo'] ?? null,
                    'unit' => $l['unit'] ?? null,
                    'cost_center' => $l['cost_center'] ?? null,
                ]);
            }

            return $j;
        });
    }
}
