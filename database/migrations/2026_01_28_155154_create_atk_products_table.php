<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atk_products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('stock')->default(0);
            $table->decimal('buy_price', 15, 2)->default(0);
            $table->decimal('sell_price_retail', 15, 2)->default(0);
            $table->decimal('sell_price_wholesale', 15, 2)->default(0);
            $table->string('unit')->default('pcs'); // pcs, box, pack, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atk_products');
    }
};
