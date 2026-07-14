<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('atk_float_transactions')) {
            Schema::create('atk_float_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('atk_float_account_id')->constrained('atk_float_accounts')->cascadeOnDelete();
                $table->enum('transaction_type', ['deposit', 'withdrawal', 'transfer', 'topup', 'ppob', 'adjustment']);
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('balance_before', 15, 2)->default(0);
                $table->decimal('balance_after', 15, 2)->default(0);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                
                $table->index(['atk_float_account_id', 'created_at']);
                $table->index(['reference_type', 'reference_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_float_transactions');
    }
};
