<?php

namespace App\Services;

use App\Models\Company;
use App\Models\GeneralTransaction;
use App\Models\IntercompanyTransaction;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;

class IntercompanyTransactionService
{
    public function __construct(
        protected AccountingPoster $accountingPoster
    ) {}

    public function createIntercompanyTransaction(
        Company $fromCompany,
        Company $toCompany,
        GeneralTransaction $sourceTransaction,
        string $description = ''
    ): IntercompanyTransaction {
        return DB::transaction(function () use ($fromCompany, $toCompany, $sourceTransaction, $description) {
            $ict = IntercompanyTransaction::create([
                'transaction_code' => IntercompanyTransaction::generateTransactionCode(),
                'from_company_id' => $fromCompany->id,
                'to_company_id' => $toCompany->id,
                'source_type' => get_class($sourceTransaction),
                'source_id' => $sourceTransaction->id,
                'amount' => $sourceTransaction->amount,
                'currency' => $sourceTransaction->currency ?? 'IDR',
                'description' => $description,
            ]);

            // Create mirror entries for both companies
            $this->createMirrorJournals($ict);

            return $ict;
        });
    }

    protected function createMirrorJournals(IntercompanyTransaction $ict): void
    {
        // From Company: Expense / Payable
        $fromJournal = $this->accountingPoster->post(
            journalNo: 'ICT-FROM-' . $ict->transaction_code,
            date: now()->format('Y-m-d'),
            description: $ict->description ?? 'Intercompany Payable',
            lines: [
                [
                    'account_id' => $this->getIntercompanyPayableAccountId($ict->from_company_id),
                    'debit' => 0,
                    'credit' => $ict->amount,
                    'memo' => 'Intercompany payable to ' . $ict->toCompany->code,
                ],
                [
                    'account_id' => $this->getIntercompanyExpenseAccountId($ict->from_company_id),
                    'debit' => $ict->amount,
                    'credit' => 0,
                    'memo' => 'Intercompany expense to ' . $ict->toCompany->code,
                ],
            ],
            periodId: null,
            sourceType: IntercompanyTransaction::class,
            sourceId: $ict->id
        );

        // To Company: Revenue / Receivable
        $toJournal = $this->accountingPoster->post(
            journalNo: 'ICT-TO-' . $ict->transaction_code,
            date: now()->format('Y-m-d'),
            description: $ict->description ?? 'Intercompany Receivable',
            lines: [
                [
                    'account_id' => $this->getIntercompanyReceivableAccountId($ict->to_company_id),
                    'debit' => $ict->amount,
                    'credit' => 0,
                    'memo' => 'Intercompany receivable from ' . $ict->fromCompany->code,
                ],
                [
                    'account_id' => $this->getIntercompanyRevenueAccountId($ict->to_company_id),
                    'debit' => 0,
                    'credit' => $ict->amount,
                    'memo' => 'Intercompany revenue from ' . $ict->fromCompany->code,
                ],
            ],
            periodId: null,
            sourceType: IntercompanyTransaction::class,
            sourceId: $ict->id
        );

        $ict->update([
            'from_journal_id' => $fromJournal->id,
            'to_journal_id' => $toJournal->id,
            'status' => 'matched',
        ]);
    }

    // Helper methods for account IDs (should be replaced with actual COA lookup)
    protected function getIntercompanyPayableAccountId(int $companyId): int
    {
        return 1; // Replace with actual COA lookup
    }

    protected function getIntercompanyReceivableAccountId(int $companyId): int
    {
        return 2; // Replace with actual COA lookup
    }

    protected function getIntercompanyExpenseAccountId(int $companyId): int
    {
        return 3; // Replace with actual COA lookup
    }

    protected function getIntercompanyRevenueAccountId(int $companyId): int
    {
        return 4; // Replace with actual COA lookup
    }
}