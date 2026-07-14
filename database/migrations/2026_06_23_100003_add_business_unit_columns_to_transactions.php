<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update invoices table
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'business_unit_id')) {
                $table->foreignId('business_unit_id')->nullable()->constrained()->after('id');
            }
            if (!Schema::hasColumn('invoices', 'profit_center_id')) {
                $table->foreignId('profit_center_id')->nullable()->constrained()->after('business_unit_id');
            }
            if (!Schema::hasColumn('invoices', 'cost_center_id')) {
                $table->foreignId('cost_center_id')->nullable()->constrained()->after('profit_center_id');
            }
        });

        // Update wash_transactions table
        Schema::table('wash_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('wash_transactions', 'business_unit_id')) {
                $table->foreignId('business_unit_id')->nullable()->constrained()->after('id');
            }
        });

        // Update atk_transactions table
        Schema::table('atk_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('atk_transactions', 'business_unit_id')) {
                $table->foreignId('business_unit_id')->nullable()->constrained()->after('id');
            }
        });

        // Update transactions table (general)
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'business_unit_id')) {
                $table->foreignId('business_unit_id')->nullable()->constrained()->after('id');
            }
        });

        // Update journal_entries table
        Schema::table('journal_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_entries', 'business_unit_id')) {
                $table->foreignId('business_unit_id')->nullable()->constrained()->after('id');
            }
            if (!Schema::hasColumn('journal_entries', 'profit_center_id')) {
                $table->foreignId('profit_center_id')->nullable()->constrained()->after('business_unit_id');
            }
        });

        // Update journals table
        Schema::table('journals', function (Blueprint $table) {
            if (!Schema::hasColumn('journals', 'business_unit_id')) {
                $table->foreignId('business_unit_id')->nullable()->constrained()->after('id');
            }
        });
    }

    public function down(): void
    {
        // We won't drop columns to maintain backward compatibility
    }
};
