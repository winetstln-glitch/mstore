<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wash_stock_movements')) {
            Schema::create('wash_stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wash_stock_item_id')->constrained('wash_stock_items')->cascadeOnDelete();
                $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
                $table->string('movement_type', 20)->index();
                $table->decimal('quantity', 14, 2);
                $table->decimal('unit_price', 14, 2)->nullable();
                $table->decimal('total_amount', 14, 2)->nullable();
                $table->date('movement_date');
                $table->string('notes')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wash_stock_movements');
    }
};
