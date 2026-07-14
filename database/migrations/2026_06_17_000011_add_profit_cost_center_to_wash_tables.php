<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->foreignId('profit_center_id')->nullable()->constrained();
            $table->foreignId('cost_center_id')->nullable()->constrained();
            $table->foreignId('wash_shift_session_id')->nullable()->constrained();
            $table->foreignId('wash_cash_register_id')->nullable()->constrained();
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'profit_center_id')) {
                $table->foreignId('profit_center_id')->nullable()->constrained();
            }
            if (!Schema::hasColumn('transactions', 'cost_center_id')) {
                $table->foreignId('cost_center_id')->nullable()->constrained();
            }
            if (!Schema::hasColumn('transactions', 'status')) {
                $table->string('status')->default('draft'); // draft, pending, approved, rejected, cancelled
            }
        });

        Schema::table('wash_stock_items', function (Blueprint $table) {
            $table->foreignId('wash_supplier_id')->nullable()->constrained();
        });
    }

    public function down()
    {
        Schema::table('wash_transactions', function (Blueprint $table) {
            $table->dropForeign(['profit_center_id']);
            $table->dropForeign(['cost_center_id']);
            $table->dropForeign(['wash_shift_session_id']);
            $table->dropForeign(['wash_cash_register_id']);
            $table->dropColumn(['profit_center_id', 'cost_center_id', 'wash_shift_session_id', 'wash_cash_register_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'profit_center_id')) {
                $table->dropForeign(['profit_center_id']);
                $table->dropColumn('profit_center_id');
            }
            if (Schema::hasColumn('transactions', 'cost_center_id')) {
                $table->dropForeign(['cost_center_id']);
                $table->dropColumn('cost_center_id');
            }
            if (Schema::hasColumn('transactions', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('wash_stock_items', function (Blueprint $table) {
            $table->dropForeign(['wash_supplier_id']);
            $table->dropColumn('wash_supplier_id');
        });
    }
};
