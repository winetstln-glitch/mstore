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
        Schema::table('wash_services', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 0)->nullable()->default(0)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wash_services', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
