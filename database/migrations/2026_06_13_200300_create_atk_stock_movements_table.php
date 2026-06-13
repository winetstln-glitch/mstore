<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('atk_stock_movements')) {
            Schema::create('atk_stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('atk_product_id')->constrained('atk_products')->cascadeOnDelete();
                $table->enum('type', ['in', 'out', 'adjustment', 'transfer']);
                $table->integer('quantity');
                $table->integer('balance_before')->default(0);
                $table->integer('balance_after')->default(0);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                
                $table->index(['atk_product_id', 'created_at']);
                $table->index(['reference_type', 'reference_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_stock_movements');
    }
};
