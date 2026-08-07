<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wash_commission_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_employee_id')->nullable()->constrained('wash_employees')->nullOnDelete();
            $table->foreignId('wash_transaction_item_id')->nullable()->constrained('wash_transaction_items')->nullOnDelete();
            $table->foreignId('wash_transaction_id')->nullable()->constrained('wash_transactions')->nullOnDelete();
            $table->string('vehicle_type_snapshot', 32)->nullable()->comment('car/motor/coffee');
            $table->string('size_tier_snapshot', 32)->nullable()->comment('kecil/sedang/besar/extra_besar');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('rate_per_unit')->default(0);
            $table->unsignedBigInteger('total_earned')->default(0);
            $table->string('status', 32)->default('earned')->index()->comment('earned/paid/voided');
            $table->timestamp('paid_at')->nullable();
            $table->nullableMorphs('paid_reference');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['wash_transaction_item_id', 'wash_employee_id'], 'uniq_commission_per_item_employee');
            $table->index(['wash_employee_id', 'status']);
            $table->index(['wash_transaction_id', 'status']);
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wash_commission_earnings');
    }
};
