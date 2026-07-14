<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intercompany_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->foreignId('from_company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('to_company_id')->constrained('companies')->onDelete('cascade');
            $table->morphs('source'); // GeneralTransaction, Expense, etc.
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->string('status')->default('pending'); // pending, matched, settled
            $table->string('elimination_status')->default('pending'); // pending, eliminated
            $table->text('description')->nullable();
            $table->foreignId('from_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->foreignId('to_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intercompany_transactions');
    }
};