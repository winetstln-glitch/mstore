<?php

namespace App\Console\Commands;

use App\Http\Controllers\FinanceController;
use Illuminate\Console\Command;

class SyncFinanceLedgerCommand extends Command
{
    protected $signature = 'accounting:sync-finance-ledger';

    protected $description = 'Sinkronkan seluruh transaksi finance ke jurnal buku besar accounting';

    public function handle(): int
    {
        $controller = app(FinanceController::class);
        $count = $controller->syncAllFinanceTransactionsToLedger();
        $this->info("Selesai sinkronisasi finance ke buku besar. Total transaksi diproses: {$count}");

        return self::SUCCESS;
    }
}
