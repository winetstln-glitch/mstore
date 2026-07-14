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
            if (!Schema::hasColumn('atk_transaction_items', 'atk_float_account_id')) {
                $table->foreignId('atk_float_account_id')->nullable()->after('fee')->constrained('atk_float_accounts')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atk_transaction_items', function (Blueprint $table) {
            if (Schema::hasColumn('atk_transaction_items', 'atk_float_account_id')) {
                $table->dropForeign(['atk_float_account_id']);
                $table->dropColumn('atk_float_account_id');
            }
        });
    }
};
