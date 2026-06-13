<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('owner_funds')) {
            Schema::create('owner_funds', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_code')->unique();
                $table->date('transaction_date');
                $table->enum('type', ['loan', 'repayment']);
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('balance', 15, 2)->default(0);
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_funds');
    }
};
