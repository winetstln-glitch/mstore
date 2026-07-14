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
            $table->string('movement_type', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atk_cash_movements', function (Blueprint $table) {
            $table->enum('movement_type', ['opening', 'sale', 'expense', 'owner_loan', 'owner_repayment', 'adjustment', 'closing'])->change();
        });
    }
};
