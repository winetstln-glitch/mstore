<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update atk_transactions table
        Schema::table('atk_transactions', function (Blueprint $table) {
            // Remove cash register references
            if (Schema::hasColumn('atk_transactions', 'atk_cash_register_id')) {
                $table->dropForeign(['atk_cash_register_id']);
                $table->dropColumn('atk_cash_register_id');
            }
            // Update transaction_type enum
            $table->string('transaction_type')->default('product')->change();
        });

        // Update atk_transaction_items table
        Schema::table('atk_transaction_items', function (Blueprint $table) {
            // Change item_type from enum to string to support all types
            $table->string('item_type')->default('product')->change();
        });
    }

    public function down(): void
    {
        // This is irreversible, but for safety:
        Schema::table('atk_transactions', function (Blueprint $table) {
            $table->enum('transaction_type', ['product', 'service', 'transfer', 'cash_withdrawal', 'topup', 'ppob', 'refund'])->default('product')->change();
            $table->foreignId('atk_cash_register_id')->nullable()->after('transaction_number')->constrained('atk_cash_registers')->nullOnDelete();
        });

        Schema::table('atk_transaction_items', function (Blueprint $table) {
            $table->enum('item_type', ['product', 'service'])->default('product')->change();
        });
    }
};
