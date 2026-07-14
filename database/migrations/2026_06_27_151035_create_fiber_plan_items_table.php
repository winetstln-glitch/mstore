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
        Schema::create('fiber_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiber_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_item_id')->nullable()->constrained()->onDelete('set null');
            $table->string('item_name');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiber_plan_items');
    }
};
