<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kasbon_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('principal', 15, 2);
            $table->date('start_date');
            $table->integer('tenor')->nullable(); // in months
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, closed
            $table->timestamps();
        });

        Schema::create('kasbon_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kasbon_loan_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->text('description')->nullable();
            $table->foreignId('salary_adjustment_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasbon_installments');
        Schema::dropIfExists('kasbon_loans');
    }
};
