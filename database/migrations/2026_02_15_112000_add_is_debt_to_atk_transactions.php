<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('atk_transactions')) {
            Schema::table('atk_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('atk_transactions', 'is_debt')) {
                    $table->boolean('is_debt')->default(false)->after('payment_method');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('atk_transactions')) {
            Schema::table('atk_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('atk_transactions', 'is_debt')) {
                    $table->dropColumn('is_debt');
                }
            });
        }
    }
};

