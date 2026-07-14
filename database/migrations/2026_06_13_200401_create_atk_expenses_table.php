<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('atk_expenses')) {
            Schema::create('atk_expenses', function (Blueprint $table) {
                $table->id();
                $table->string('expense_number')->nullable();
                $table->foreignId('atk_expense_category_id')->nullable()->constrained('atk_expense_categories')->nullOnDelete();
                $table->decimal('amount', 15, 2)->default(0);
                $table->text('description')->nullable();
                $table->string('receipt_photo')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_expenses');
    }
};
