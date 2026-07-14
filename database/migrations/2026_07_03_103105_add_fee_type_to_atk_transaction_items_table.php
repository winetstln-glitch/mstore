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
        Schema::table('atk_transaction_items', function (Blueprint $table) {
            $table->string('fee_type')->nullable()->after('fee'); // 'outside' or 'inside'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atk_transaction_items', function (Blueprint $table) {
            $table->dropColumn('fee_type');
        });
    }
};
