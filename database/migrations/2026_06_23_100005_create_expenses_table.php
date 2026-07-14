<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('profit_center_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('cost_center_id')->nullable()->constrained()->onDelete('set null');
            $table->string('expense_number', 100)->unique();
            $table->foreignId('expense_category_id')->nullable()->constrained()->onDelete('set null');
            $table->date('transaction_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'paid'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('business_unit_id');
            $table->index('status');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
