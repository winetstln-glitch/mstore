<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wash_loyalty_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_customer_id')->nullable()->constrained('wash_customers')->nullOnDelete();
            $table->string('vehicle_plate')->index();
            $table->unsignedInteger('cycle_paid_count')->default(0);
            $table->unsignedInteger('lifetime_paid_count')->default(0);
            $table->foreignId('last_paid_transaction_id')->nullable()->constrained('wash_transactions')->nullOnDelete();
            $table->timestamp('last_paid_at')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_plate']);
        });

        Schema::create('wash_reward_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('wash_loyalty_counter_id')->constrained('wash_loyalty_counters')->cascadeOnDelete();
            $table->foreignId('wash_customer_id')->nullable()->constrained('wash_customers')->nullOnDelete();
            $table->string('vehicle_plate')->index();
            $table->string('reward_type', 64)->index();
            $table->string('status', 16)->default('available')->index();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_wash_transaction_id')->nullable()->constrained('wash_transactions')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('wash_reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_reward_voucher_id')->constrained('wash_reward_vouchers')->cascadeOnDelete();
            $table->foreignId('wash_transaction_id')->constrained('wash_transactions')->cascadeOnDelete();
            $table->foreignId('redeemed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount')->default(0);
            $table->timestamp('redeemed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wash_reward_redemptions');
        Schema::dropIfExists('wash_reward_vouchers');
        Schema::dropIfExists('wash_loyalty_counters');
    }
};

