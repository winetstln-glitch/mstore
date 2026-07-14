<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wash_transaction_items')) {
            Schema::table('wash_transaction_items', function (Blueprint $table) {
                if (! Schema::hasColumn('wash_transaction_items', 'base_price')) {
                    $table->decimal('base_price', 15, 2)->nullable()->after('service_name');
                }
                if (! Schema::hasColumn('wash_transaction_items', 'holiday_adjustment')) {
                    $table->decimal('holiday_adjustment', 15, 2)->nullable()->after('base_price');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wash_transaction_items')) {
            Schema::table('wash_transaction_items', function (Blueprint $table) {
                if (Schema::hasColumn('wash_transaction_items', 'holiday_adjustment')) {
                    $table->dropColumn('holiday_adjustment');
                }
                if (Schema::hasColumn('wash_transaction_items', 'base_price')) {
                    $table->dropColumn('base_price');
                }
            });
        }
    }
};
