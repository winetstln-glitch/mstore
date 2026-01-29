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
            $table->enum('vehicle_type', ['car', 'motor']);
            $table->decimal('price', 12, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wash_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique(); // e.g., WSH-20240101-001
            $table->string('customer_name')->nullable();
            $table->string('plate_number')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('payment_method')->default('cash'); // cash, qris, transfer
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('user_id')->constrained('users'); // Cashier
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('wash_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_transaction_id')->constrained('wash_transactions')->onDelete('cascade');
            $table->foreignId('wash_service_id')->constrained('wash_services');
            $table->decimal('price', 12, 2); // Snapshot of price at transaction time
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 12, 2);
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
    }
};
