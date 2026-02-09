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
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('wash_transactions', 'transaction_code')) {
                 try {
                    $table->dropUnique('wash_transactions_transaction_code_unique');
                } catch (\Exception $e) {
                }
                $table->dropColumn('transaction_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('wash_transactions', 'transaction_code')) {
                $table->string('transaction_code')->nullable()->unique();
            }
        });
    }
};
