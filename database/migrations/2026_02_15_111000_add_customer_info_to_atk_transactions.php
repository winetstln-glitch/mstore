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
                if (!Schema::hasColumn('atk_transactions', 'customer_name')) {
                    $table->string('customer_name')->nullable()->after('coordinator_id');
                }
                if (!Schema::hasColumn('atk_transactions', 'customer_phone')) {
                    $table->string('customer_phone', 50)->nullable()->after('customer_name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('atk_transactions')) {
            Schema::table('atk_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('atk_transactions', 'customer_phone')) {
                    $table->dropColumn('customer_phone');
                }
                if (Schema::hasColumn('atk_transactions', 'customer_name')) {
                    $table->dropColumn('customer_name');
                }
            });
        }
    }
};

