<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class LedgerReversalService
{
    public function __construct(
        protected AccountingPoster $accountingPoster
    ) {}

    public function reverseJournalEntry(int $journalEntryId, string $reason = ''): Journal
    {
        $originalEntry = JournalEntry::findOrFail($journalEntryId);
        $originalJournal = $originalEntry->journal;

        return DB::transaction(function () use ($originalEntry, $originalJournal, $reason) {
            $reversalJournalNo = 'REV-' . $originalJournal->journal_no;
            $reversalDate = now()->format('Y-m-d');
            $memo = "Reversal of journal entry #{$originalEntry->id} - " . ($reason ?: 'Manual reversal');

            $reversalEntries = [];

            $allOriginalEntries = $originalJournal->entries;
            foreach ($allOriginalEntries as $entry) {
                $reversalEntries[] = [
                    'account_id' => $entry->account_id,
                    'debit' => $entry->credit,
                    'credit' => $entry->debit,
                    'memo' => $memo,
                    'reversal_of_id' => $entry->id,
                ];
            }

            return $this->accountingPoster->post(
                journalNo: $reversalJournalNo,
                date: $reversalDate,
                description: $memo,
                lines: $reversalEntries
            );
        });
    }
}