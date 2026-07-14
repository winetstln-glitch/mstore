<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wash_services') && ! Schema::hasColumn('wash_services', 'holiday_price')) {
            Schema::table('wash_services', function (Blueprint $table) {
                $table->decimal('holiday_price', 15, 2)->nullable()->after('price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wash_services') && Schema::hasColumn('wash_services', 'holiday_price')) {
            Schema::table('wash_services', function (Blueprint $table) {
                $table->dropColumn('holiday_price');
            });
        }
    }
};
