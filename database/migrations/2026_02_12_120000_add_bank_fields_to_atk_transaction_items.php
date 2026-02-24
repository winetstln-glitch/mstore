<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('atk_transaction_items')) {
            Schema::table('atk_transaction_items', function (Blueprint $table) {
                if (! Schema::hasColumn('atk_transaction_items', 'nominal_transaksi')) {
                    $table->decimal('nominal_transaksi', 15, 2)->nullable()->after('subtotal');
                }
                if (! Schema::hasColumn('atk_transaction_items', 'fee')) {
                    $table->decimal('fee', 15, 2)->nullable()->after('nominal_transaksi');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('atk_transaction_items')) {
            Schema::table('atk_transaction_items', function (Blueprint $table) {
                if (Schema::hasColumn('atk_transaction_items', 'fee')) {
                    $table->dropColumn('fee');
                }
                if (Schema::hasColumn('atk_transaction_items', 'nominal_transaksi')) {
                    $table->dropColumn('nominal_transaksi');
                }
            });
        }
    }
};
