<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wash_stock_items')) {
            Schema::create('wash_stock_items', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category', 40)->index();
                $table->string('unit', 20)->default('pcs');
                $table->decimal('current_stock', 14, 2)->default(0);
                $table->decimal('minimum_stock', 14, 2)->default(0);
                $table->decimal('last_buy_price', 14, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wash_stock_items');
    }
};
