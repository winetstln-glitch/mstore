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
        Schema::create('wash_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('vehicle_type', ['car', 'motor'])->default('car');
            $table->decimal('price', 15, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('transaction_number')->unique();
            $table->string('customer_name')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_method')->default('cash');
            $table->decimal('cash_amount', 15, 2)->nullable();
            $table->decimal('change_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('wash_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_transaction_id')->constrained('wash_transactions')->cascadeOnDelete();
            $table->foreignId('wash_service_id')->nullable()->constrained('wash_services')->nullOnDelete();
            $table->string('service_name'); // Snapshot of name
            $table->decimal('price', 15, 2); // Snapshot of price
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wash_transaction_items');
        Schema::dropIfExists('wash_transactions');
        Schema::dropIfExists('wash_services');
        Schema::dropIfExists('wash_services_tables');
    }
};
