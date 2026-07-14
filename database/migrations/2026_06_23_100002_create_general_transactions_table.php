<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('profit_center_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('cost_center_id')->nullable()->constrained()->onDelete('set null');
            $table->string('transaction_code', 100)->unique();
            $table->enum('transaction_type', [
                'invoice', 'payment', 'expense', 'wash', 'atk', 'cctv', 'wedding', 'refund', 'adjustment'
            ]);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->text('description')->nullable();
            $table->morphs('reference'); // reference_type & reference_id
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('business_unit_id');
            $table->index('transaction_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_transactions');
    }
};
