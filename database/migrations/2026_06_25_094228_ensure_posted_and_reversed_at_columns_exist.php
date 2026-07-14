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
        // ATK Transactions
        Schema::table('atk_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('atk_transactions', 'status')) {
                $table->string('status')->default('draft')->after('transaction_number');
            }
            if (!Schema::hasColumn('atk_transactions', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('atk_transactions', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('posted_at');
            }
        });

        // Wash Transactions
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('wash_transactions', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('wash_transactions', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('posted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
