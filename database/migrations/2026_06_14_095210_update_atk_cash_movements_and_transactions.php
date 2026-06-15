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
            $table->foreignId('atk_cash_register_id')->nullable()->change();
        });

        Schema::table('atk_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('atk_transactions', 'atk_cash_register_id')) {
                $table->foreignId('atk_cash_register_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atk_cash_movements', function (Blueprint $table) {
            $table->foreignId('atk_cash_register_id')->nullable(false)->change();
        });

        Schema::table('atk_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('atk_transactions', 'atk_cash_register_id')) {
                $table->foreignId('atk_cash_register_id')->nullable(false)->change();
            }
        });
    }
};
