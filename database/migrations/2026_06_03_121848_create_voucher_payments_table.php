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
        Schema::create('voucher_payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_id')->unique();
            $table->string('phone_number');
            $table->foreignId('voucher_template_id')->constrained('voucher_templates')->onDelete('cascade');
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('pending'); // pending, paid, failed, expired
            $table->string('payment_method')->nullable(); // qris, etc.
            $table->string('payment_reference')->nullable(); // reference from payment gateway
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->onDelete('set null');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('qr_data')->nullable(); // QR code content or URL
            $table->text('qr_url')->nullable(); // URL to view QR code
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_payments');
    }
};
