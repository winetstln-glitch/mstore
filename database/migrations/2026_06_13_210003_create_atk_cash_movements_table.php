<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('atk_cash_movements')) {
            Schema::create('atk_cash_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('atk_cash_register_id')->constrained('atk_cash_registers')->cascadeOnDelete();
                $table->enum('movement_type', ['opening', 'sale', 'expense', 'owner_loan', 'owner_repayment', 'adjustment', 'closing']);
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('balance_before', 15, 2)->default(0);
                $table->decimal('balance_after', 15, 2)->default(0);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                
                $table->index(['atk_cash_register_id', 'created_at']);
                $table->index(['reference_type', 'reference_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_cash_movements');
    }
};
