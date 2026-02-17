<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCashChangeToTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Tambah kolom cash dan change
            $table->decimal('cash_amount', 15, 2)->default(0)->after('method');
            $table->decimal('change_amount', 15, 2)->default(0)->after('cash_amount');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['cash_amount', 'change_amount']);
        });
    }
}
