<?php

namespace App\Console\Commands;

use App\Events\GeneralTransactionCreated;
use App\Models\AtkTransaction;
use App\Models\BusinessUnit;
use App\Models\GeneralTransaction;
use App\Models\Invoice;
use App\Models\WashTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillGeneralTransactions extends Command
{
    protected $signature = 'erp:backfill-transactions';
    protected $description = 'Backfill all existing transactions to General Transactions and Ledger';

    public function handle(): int
    {
        $this->info('Starting backfill process...');

        $this->seedBusinessUnits();
        $this->backfillWashTransactions();
        $this->backfillAtkTransactions();
        $this->backfillInvoices();

        $this->info('Backfill completed successfully!');
        return self::SUCCESS;
    }

    private function seedBusinessUnits(): void
    {
        $this->info('Seeding business units...');
        
        $businessUnits = [
            ['code' => 'ISP', 'name' => 'ISP Internet Provider', 'type' => 'ISP'],
            ['code' => 'ATK', 'name' => 'Toko ATK', 'type' => 'RETAIL'],
            ['code' => 'WASH', 'name' => 'Wash & Detailing', 'type' => 'SERVICE'],
            ['code' => 'CCTV', 'name' => 'CCTV Installation', 'type' => 'SERVICE'],
            ['code' => 'WEDDING', 'name' => 'Wedding Organizer', 'type' => 'SERVICE'],
        ];

        foreach ($businessUnits as $bu) {
            BusinessUnit::firstOrCreate(['code' => $bu['code']], $bu);
        }

        $this->info('Business units seeded!');
    }

    private function backfillWashTransactions(): void
    {
        $this->info('Backfilling Wash Transactions...');

        $businessUnit = BusinessUnit::where('code', 'WASH')->first();
        $washTransactions = WashTransaction::whereDoesntHave('generalTransactions')->get();

        $progressBar = $this->output->createProgressBar(count($washTransactions));
        $progressBar->start();

        foreach ($washTransactions as $transaction) {
            try {
                $gt = GeneralTransaction::firstOrCreate(
                    ['reference_type' => WashTransaction::class, 'reference_id' => $transaction->id],
                    [
                        'business_unit_id' => $businessUnit->id,
                        'profit_center_id' => $transaction->profit_center_id,
                        'cost_center_id' => $transaction->cost_center_id,
                        'transaction_type' => 'wash',
                        'transaction_code' => 'GT-WASH-' . $transaction->transaction_number,
                        'amount' => $transaction->total_amount,
                        'status' => 'posted',
                        'description' => 'Transaksi wash ' . $transaction->transaction_number,
                        'created_by' => $transaction->user_id,
                    ]
                );

                event(new GeneralTransactionCreated($gt));
            } catch (\Exception $e) {
                Log::error('Failed to backfill wash transaction', [
                    'id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->newLine();
    }

    private function backfillAtkTransactions(): void
    {
        $this->info('Backfilling ATK Transactions...');

        $businessUnit = BusinessUnit::where('code', 'ATK')->first();
        $atkTransactions = AtkTransaction::whereDoesntHave('generalTransactions')->get();

        $progressBar = $this->output->createProgressBar(count($atkTransactions));
        $progressBar->start();

        foreach ($atkTransactions as $transaction) {
            try {
                $gt = GeneralTransaction::firstOrCreate(
                    ['reference_type' => AtkTransaction::class, 'reference_id' => $transaction->id],
                    [
                        'business_unit_id' => $businessUnit->id,
                        'transaction_type' => 'atk',
                        'transaction_code' => 'GT-ATK-' . $transaction->transaction_number,
                        'amount' => $transaction->total_amount,
                        'status' => 'posted',
                        'description' => 'Transaksi ATK ' . $transaction->transaction_number,
                        'created_by' => $transaction->user_id,
                    ]
                );

                event(new GeneralTransactionCreated($gt));
            } catch (\Exception $e) {
                Log::error('Failed to backfill atk transaction', [
                    'id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->newLine();
    }

    private function backfillInvoices(): void
    {
        $this->info('Backfilling ISP Invoices...');

        $businessUnit = BusinessUnit::where('code', 'ISP')->first();
        $invoices = Invoice::where('status', 'paid')->whereDoesntHave('generalTransactions')->get();

        $progressBar = $this->output->createProgressBar(count($invoices));
        $progressBar->start();

        foreach ($invoices as $invoice) {
            try {
                $gt = GeneralTransaction::firstOrCreate(
                    ['reference_type' => Invoice::class, 'reference_id' => $invoice->id],
                    [
                        'business_unit_id' => $businessUnit->id,
                        'transaction_type' => 'invoice',
                        'transaction_code' => 'GT-INV-' . $invoice->code,
                        'amount' => $invoice->amount,
                        'status' => 'posted',
                        'description' => 'Pembayaran invoice ' . $invoice->code,
                    ]
                );

                event(new GeneralTransactionCreated($gt));
            } catch (\Exception $e) {
                Log::error('Failed to backfill invoice', [
                    'id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->newLine();
    }
}
