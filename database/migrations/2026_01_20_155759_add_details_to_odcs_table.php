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
        Schema::table('odcs', function (Blueprint $table) {
            $table->string('pon_port')->nullable()->after('olt_id');
            $table->string('area')->nullable()->after('pon_port');
            $table->string('color')->nullable()->after('area');
            $table->string('cable_no')->nullable()->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odcs', function (Blueprint $table) {
            $table->dropColumn(['pon_port', 'area', 'color', 'cable_no']);
        });
    }
};
