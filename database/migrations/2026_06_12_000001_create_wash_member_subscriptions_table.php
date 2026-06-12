<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wash_member_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_member_id')->constrained('wash_members')->cascadeOnDelete();
            $table->foreignId('wash_member_package_id')->constrained('wash_member_packages')->cascadeOnDelete();
            $table->foreignId('wash_transaction_id')->nullable()->constrained('wash_transactions')->nullOnDelete();
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->string('status', 16)->default('active'); // active, expired, canceled
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['wash_member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wash_member_subscriptions');
    }
};
