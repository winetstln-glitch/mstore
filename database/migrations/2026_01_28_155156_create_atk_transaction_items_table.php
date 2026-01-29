<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atk_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atk_transaction_id')->constrained('atk_transactions')->onDelete('cascade');
            $table->foreignId('atk_product_id')->constrained('atk_products')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price', 15, 2); // Price per unit at time of transaction
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_transaction_items');
    }
};
