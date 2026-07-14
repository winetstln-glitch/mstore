<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->onDelete('cascade');
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_items');
    }
};
