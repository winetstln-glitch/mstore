<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetAtkTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-atk-transactions';

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
        $this->warn('PERINGATAN: Ini akan menghapus SEMUA data transaksi ATK!');
        $this->warn('Termasuk: Transaksi POS, Mutasi Kas, Mutasi Float Akun, dll.');
        
        if (!$this->confirm('Apakah Anda yakin ingin melanjutkan?')) {
            $this->info('Operasi dibatalkan.');
            return;
        }

        if (!$this->confirm('YAKIN? Semua data transaksi akan hilang PERMANEN!')) {
            $this->info('Operasi dibatalkan.');
            return;
        }

        $this->info('Memulai reset transaksi ATK...');

        \DB::beginTransaction();
        try {
            // Hapus semua transaksi dan itemnya
            \App\Models\AtkTransactionItem::query()->delete();
            \App\Models\AtkTransaction::query()->delete();
            
            // Hapus semua mutasi kas ATK
            \App\Models\AtkCashMovement::query()->delete();
            \App\Models\AtkFloatTransaction::query()->delete(); 
            
            // Reset saldo semua akun float menjadi 0
            \App\Models\AtkFloatAccount::query()->update([
                'current_balance' => 0
            ]);
            
            // Reset saldo Kas Utama menjadi 0
            $cash = \App\Models\Cash::firstOrCreate(['name' => 'Kas Utama'], ['balance' => 0]);
            $cash->update(['balance' => 0]);
            
            // Hapus semua journal jika ada
            if (\Schema::hasTable('journals')) {
                \DB::table('journals')->where('source_type', 'atk_transaction')->delete();
                \DB::table('journals')->where('source_type', 'atk_float_transaction')->delete();
            }
            
            \DB::commit();
            
            $this->info('✅ BERHASIL: Semua transaksi ATK berhasil direset!');
            $this->info('   - Kas Utama: Rp 0');
            $this->info('   - Semua Akun Float: Rp 0');
        } catch (\Exception $e) {
            \DB::rollBack();
            $this->error('❌ GAGAL: ' . $e->getMessage());
            $this->error('   File: ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}
