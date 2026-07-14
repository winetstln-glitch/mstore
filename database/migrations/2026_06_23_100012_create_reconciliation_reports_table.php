<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_reports', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('business_unit_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('total_transactions');
            $table->integer('total_journal_entries');
            $table->decimal('difference', 15, 2)->default(0);
            $table->enum('status', ['balanced', 'mismatch', 'critical']);
            $table->json('details_json')->nullable();
            $table->timestamps();

            $table->unique(['date', 'business_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_reports');
    }
};