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
                if (!Schema::hasColumn('atk_transactions', 'due_date')) {
                    $table->date('due_date')->nullable()->after('customer_phone');
                }
                if (!Schema::hasColumn('atk_transactions', 'is_settled')) {
                    $table->boolean('is_settled')->default(false)->after('is_debt');
                }
                if (!Schema::hasColumn('atk_transactions', 'settled_at')) {
                    $table->timestamp('settled_at')->nullable()->after('is_settled');
                }
                if (!Schema::hasColumn('atk_transactions', 'settled_amount')) {
                    $table->decimal('settled_amount', 16, 2)->default(0)->after('settled_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('atk_transactions')) {
            Schema::table('atk_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('atk_transactions', 'settled_amount')) {
                    $table->dropColumn('settled_amount');
                }
                if (Schema::hasColumn('atk_transactions', 'settled_at')) {
                    $table->dropColumn('settled_at');
                }
                if (Schema::hasColumn('atk_transactions', 'is_settled')) {
                    $table->dropColumn('is_settled');
                }
                if (Schema::hasColumn('atk_transactions', 'due_date')) {
                    $table->dropColumn('due_date');
                }
            });
        }
    }
};

