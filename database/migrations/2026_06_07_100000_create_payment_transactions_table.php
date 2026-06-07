<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_transactions')) {
            return;
        }
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_id')->unique();
            $table->morphs('paymentable'); // For polymorphic relation to Invoice, VoucherPayment, Installation, etc.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('payment_type')->default('QRIS'); // QRIS, VA, CC, etc.
            $table->string('payment_method')->nullable(); // Duitku, Midtrans, etc.
            $table->string('payment_gateway')->default('duitku'); // duitku, midtrans, xendit
            $table->string('gateway_reference_id')->nullable()->index();
            $table->enum('status', ['pending', 'paid', 'failed', 'expired'])->default('pending');
            $table->string('qr_url')->nullable();
            $table->text('qr_data')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
