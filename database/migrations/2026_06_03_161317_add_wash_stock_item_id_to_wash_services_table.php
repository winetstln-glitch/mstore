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
        Schema::table('wash_services', function (Blueprint $table) {
            $table->foreignId('wash_stock_item_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wash_services', function (Blueprint $table) {
            $table->dropForeign(['wash_stock_item_id']);
            $table->dropColumn('wash_stock_item_id');
        });
    }
};
