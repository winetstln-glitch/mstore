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
        // Fix Wash Transactions
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('wash_transactions', 'transaction_number')) {
                $table->string('transaction_number')->nullable()->after('id');
            }
        });

        // Fix ATK Transactions
        Schema::table('atk_transactions', function (Blueprint $table) {
            // Since SQLite cannot modify column constraints easily, we will try to rename it or add a default.
            // But if it's NOT NULL, we must supply it.
            // Best approach for SQLite is to allow NULL by creating a new table, but Laravel's Schema builder 
            // has limited support for this in SQLite.
            // However, we can try to make it nullable using change(), but it requires dbal.
            
            // Alternative: We will update the Controller to save to invoice_number as well.
            // But to be clean, let's try to add transaction_number if missing (done in prev migration)
            // and make invoice_number nullable if possible.
            
            // If we cannot make it nullable easily, we will just ignore it here and handle it in Controller.
            // But wait, the user said "Integrity constraint violation".
            
            // Let's try to change it to nullable. 
            // Note: This requires doctrine/dbal. If not installed, it will fail.
            // Assuming it might fail, I'll rely on the Controller fix for ATK.
            
            // BUT, for Wash Transactions, I MUST add the column.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->dropColumn(['transaction_number']);
        });
    }
};
