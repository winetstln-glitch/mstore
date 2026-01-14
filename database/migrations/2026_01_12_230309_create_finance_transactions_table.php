<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Creator
                $table->foreignId('coordinator_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('type', ['income', 'expense']);
                $table->string('category'); // member, voucher, salary, operational, etc.
                $table->decimal('amount', 15, 2);
                $table->text('description')->nullable();
                $table->date('transaction_date');
                $table->string('reference_number')->nullable(); // e.g., Invoice ID or Receipt No
                $table->timestamps();
            });
        } else {
            Schema::table('transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('transactions', 'coordinator_id')) {
                    $table->foreignId('coordinator_id')->nullable()->constrained()->nullOnDelete();
                }
                if (!Schema::hasColumn('transactions', 'category')) {
                    $table->string('category')->default('general');
                }
                if (!Schema::hasColumn('transactions', 'type')) {
                    $table->enum('type', ['income', 'expense'])->default('income');
                }
                if (!Schema::hasColumn('transactions', 'reference_number')) {
                    $table->string('reference_number')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
