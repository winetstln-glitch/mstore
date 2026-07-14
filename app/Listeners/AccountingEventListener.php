<?php

namespace App\Listeners;

use App\Events\AtkTransactionCreated;
use App\Events\ExpenseApproved;
use App\Events\GeneralTransactionCreated;
use App\Events\InvoicePaidEvent;
use App\Events\WashTransactionCreated;
use App\Models\Account;
use App\Models\BusinessUnit;
use App\Models\GeneralTransaction;
use App\Services\AccountingPoster;
use Illuminate\Support\Facades\Log;

class AccountingEventListener
{
    public function __construct(
        public readonly AccountingPoster $accountingPoster
    ) {}

    /**
     * Handle General Transaction Created
     */
    public function handleGeneralTransactionCreated(GeneralTransactionCreated $event): void
    {
        $transaction = $event->transaction;

        if ($transaction->status !== 'posted') {
            return;
        }

        try {
            $this->postTransactionToLedger($transaction);
        } catch (\Exception $e) {
            Log::error('Failed to post transaction to ledger', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle Invoice Paid
     */
    public function handleInvoicePaid(InvoicePaidEvent $event): void
    {
        $invoice = $event->invoice;

        // Get or create business unit
        $businessUnit = BusinessUnit::firstOrCreate(['code' => 'ISP'], ['name' => 'ISP Internet Provider', 'type' => 'ISP']);

        // Create general transaction
        $generalTransaction = GeneralTransaction::firstOrCreate(
            [
                'reference_type' => \App\Models\Invoice::class,
                'reference_id' => $invoice->id,
            ],
            [
                'business_unit_id' => $businessUnit->id,
                'transaction_type' => 'invoice',
                'transaction_code' => 'GT-INV-' . $invoice->code,
                'amount' => $invoice->amount,
                'currency' => 'IDR',
                'status' => 'posted',
                'description' => 'Pembayaran invoice ' . $invoice->code,
                'created_by' => auth()->id(),
                'profit_center_id' => $invoice->profit_center_id,
                'cost_center_id' => $invoice->cost_center_id,
            ]
        );

        // Post to ledger
        $this->postTransactionToLedger($generalTransaction);
    }

    /**
     * Handle Wash Transaction Created
     */
    public function handleWashTransactionCreated(WashTransactionCreated $event): void
    {
        $washTransaction = $event->transaction;

        // Guard clauses: only process valid transactions
        if ($washTransaction->status !== 'posted') {
            Log::info('Skipping Wash Transaction event: not posted', [
                'transaction_id' => $washTransaction->id,
                'status' => $washTransaction->status,
            ]);
            return;
        }

        if ($washTransaction->reversed_at !== null) {
            Log::info('Skipping Wash Transaction event: already reversed', [
                'transaction_id' => $washTransaction->id,
            ]);
            return;
        }

        $washTransaction->syncAccountingJournal();
    }

    /**
     * Handle ATK Transaction Created
     */
    public function handleAtkTransactionCreated(AtkTransactionCreated $event): void
    {
        $atkTransaction = $event->transaction;

        // 🛑 Guard clauses: only process valid transactions!
        if ($atkTransaction->status !== 'posted') {
            \Illuminate\Support\Facades\Log::info('Skipping ATK Transaction event: not posted', [
                'transaction_id' => $atkTransaction->id,
                'status' => $atkTransaction->status,
            ]);
            return;
        }

        if ($atkTransaction->reversed_at !== null) {
            \Illuminate\Support\Facades\Log::info('Skipping ATK Transaction event: already reversed', [
                'transaction_id' => $atkTransaction->id,
            ]);
            return;
        }

        // Use AtkTransaction's detailed syncAccountingJournal for proper journal entries!
        $atkTransaction->syncAccountingJournal();
    }

    /**
     * Handle Expense Approved
     */
    public function handleExpenseApproved(ExpenseApproved $event): void
    {
        $expense = $event->expense;

        $generalTransaction = GeneralTransaction::firstOrCreate(
            [
                'reference_type' => \App\Models\Expense::class,
                'reference_id' => $expense->id,
            ],
            [
                'business_unit_id' => $expense->business_unit_id,
                'branch_id' => $expense->branch_id,
                'profit_center_id' => $expense->profit_center_id,
                'cost_center_id' => $expense->cost_center_id,
                'transaction_type' => 'expense',
                'transaction_code' => 'GT-EXP-' . $expense->expense_number,
                'amount' => $expense->total_amount,
                'currency' => 'IDR',
                'status' => 'posted',
                'description' => 'Pengeluaran ' . $expense->expense_number,
                'created_by' => $expense->created_by,
                'approved_by' => $expense->updated_by,
            ]
        );

        $this->postTransactionToLedger($generalTransaction);
    }

    /**
     * Post transaction to General Ledger
     */
    private function postTransactionToLedger(GeneralTransaction $transaction): void
    {
        $accountMapping = $this->getAccountMapping($transaction->transaction_type);
        
        $lines = [];
        
        // Example mapping: debit Kas, credit Pendapatan
        $lines[] = [
            'account_id' => $accountMapping['debit'],
            'debit' => $transaction->amount,
            'credit' => 0,
            'memo' => $transaction->description,
        ];
        
        $lines[] = [
            'account_id' => $accountMapping['credit'],
            'debit' => 0,
            'credit' => $transaction->amount,
            'memo' => $transaction->description,
        ];

        $journalNo = 'J-' . $transaction->transaction_code;
        $date = $transaction->created_at->format('Y-m-d');

        $this->accountingPoster->post(
            $journalNo,
            $date,
            $transaction->description,
            $lines,
            null,
            \App\Models\GeneralTransaction::class,
            $transaction->id
        );

        Log::info('Transaction posted to ledger', ['transaction_id' => $transaction->id]);
    }

    /**
     * Get account mapping per transaction type
     */
    private function getAccountMapping(string $transactionType): array
    {
        $debitCode = match ($transactionType) {
            'invoice', 'wash', 'atk' => '1001', // Kas
            'expense' => '5001', // Beban
            default => '1001',
        };

        $creditCode = match ($transactionType) {
            'invoice' => '4001', // Pendapatan Jasa
            'wash' => '4005', // Pendapatan Wash
            'atk' => '4003', // Pendapatan ATK
            'expense' => '1001', // Kas
            default => '4001',
        };

        return [
            'debit' => Account::where('code', $debitCode)->first()?->id ?? 1,
            'credit' => Account::where('code', $creditCode)->first()?->id ?? 2,
        ];
    }
}
