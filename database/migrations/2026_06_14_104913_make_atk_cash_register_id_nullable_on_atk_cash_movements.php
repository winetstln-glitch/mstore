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
        Schema::table('atk_cash_movements', function (Blueprint $table) {
            $table->dropForeign(['atk_cash_register_id']);
            $table->foreignId('atk_cash_register_id')->nullable()->change();
            $table->foreign('atk_cash_register_id')->references('id')->on('atk_cash_registers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atk_cash_movements', function (Blueprint $table) {
            $table->dropForeign(['atk_cash_register_id']);
            $table->foreignId('atk_cash_register_id')->nullable(false)->change();
            $table->foreign('atk_cash_register_id')->references('id')->on('atk_cash_registers')->cascadeOnDelete();
        });
    }
};
