<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_analytics_events', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at')->index();

            $table->string('direction', 10)->index();
            $table->string('phone_number', 32)->index();
            $table->foreignId('whatsapp_session_id')->nullable()->constrained('whatsapp_sessions')->nullOnDelete();

            $table->string('intent', 50)->nullable()->index();
            $table->boolean('used_ai')->default(false)->index();
            $table->boolean('is_fallback')->default(false)->index();

            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
            $table->foreignId('voucher_payment_id')->nullable()->constrained('voucher_payments')->nullOnDelete();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['direction', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_analytics_events');
    }
};

