<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'general_transactions',
            'journals',
            'journal_entries',
            'outbox_events',
            'reconciliation_reports',
            'expenses',
            'daily_summaries',
            'monthly_summaries',
            'yearly_summaries',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
                });
            }
            if (!Schema::hasColumn($table, 'company_branch_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('company_branch_id')->nullable()->constrained('company_branches')->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'general_transactions',
            'journals',
            'journal_entries',
            'outbox_events',
            'reconciliation_reports',
            'expenses',
            'daily_summaries',
            'monthly_summaries',
            'yearly_summaries',
        ];

        foreach (array_reverse($tables) as $table) {
            if (Schema::hasColumn($table, 'company_branch_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['company_branch_id']);
                    $table->dropColumn('company_branch_id');
                });
            }
            if (Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                });
            }
        }
    }
};