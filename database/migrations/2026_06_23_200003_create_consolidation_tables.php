<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consolidation_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type'); // balance_sheet, income_statement, cash_flow
            $table->date('start_date');
            $table->date('end_date');
            $table->string('currency', 3)->default('IDR');
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_expense', 15, 2)->default(0);
            $table->decimal('intercompany_eliminations', 15, 2)->default(0);
            $table->decimal('consolidated_profit', 15, 2)->default(0);
            $table->enum('status', ['draft', 'final', 'approved'])->default('draft');
            $table->timestamps();
        });

        Schema::create('consolidation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consolidation_report_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('account_code');
            $table->string('account_name');
            $table->decimal('amount', 15, 2);
            $table->decimal('eliminated_amount', 15, 2)->default(0);
            $table->decimal('consolidated_amount', 15, 2)->default(0);
            $table->string('item_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consolidation_items');
        Schema::dropIfExists('consolidation_reports');
    }
};