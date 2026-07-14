<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('profit_center_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('cost_center_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('summary_date');

            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_expense', 15, 2)->default(0);
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->integer('total_transactions')->default(0);
            $table->integer('total_customers_served')->default(0);

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['business_unit_id', 'branch_id', 'profit_center_id', 'cost_center_id', 'summary_date'], 'daily_summary_unique');
        });

        Schema::create('monthly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('profit_center_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('cost_center_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('month');

            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_expense', 15, 2)->default(0);
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->integer('total_transactions')->default(0);
            $table->integer('total_customers_served')->default(0);

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['business_unit_id', 'branch_id', 'profit_center_id', 'cost_center_id', 'year', 'month'], 'monthly_summary_unique');
        });

        Schema::create('yearly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('profit_center_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('cost_center_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('year');

            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_expense', 15, 2)->default(0);
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->integer('total_transactions')->default(0);
            $table->integer('total_customers_served')->default(0);

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['business_unit_id', 'branch_id', 'profit_center_id', 'cost_center_id', 'year'], 'yearly_summary_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yearly_summaries');
        Schema::dropIfExists('monthly_summaries');
        Schema::dropIfExists('daily_summaries');
    }
};