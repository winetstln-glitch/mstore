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
        Schema::table('atk_cash_registers', function (Blueprint $table) {
            // Rename initial_balance to opening_balance
            if (Schema::hasColumn('atk_cash_registers', 'initial_balance')) {
                $table->renameColumn('initial_balance', 'opening_balance');
            }
            // Rename final_balance to closing_balance
            if (Schema::hasColumn('atk_cash_registers', 'final_balance')) {
                $table->renameColumn('final_balance', 'closing_balance');
            }
            // Rename opened_by to user_id and add foreign key
            if (Schema::hasColumn('atk_cash_registers', 'opened_by')) {
                $table->dropForeign(['opened_by']);
                $table->renameColumn('opened_by', 'user_id');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            } else {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }
            // Remove closed_by since we don't use it right now
            if (Schema::hasColumn('atk_cash_registers', 'closed_by')) {
                $table->dropForeign(['closed_by']);
                $table->dropColumn('closed_by');
            }
            // Remove notes since we don't use it right now
            if (Schema::hasColumn('atk_cash_registers', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atk_cash_registers', function (Blueprint $table) {
            // Rename back
            $table->renameColumn('opening_balance', 'initial_balance');
            $table->renameColumn('closing_balance', 'final_balance');
            $table->dropForeign(['user_id']);
            $table->renameColumn('user_id', 'opened_by');
            $table->foreign('opened_by')->references('id')->on('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
        });
    }
};
