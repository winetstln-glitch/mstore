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
        Schema::table('closures', function (Blueprint $table) {
            $table->string('color')->nullable()->after('name');
            $table->integer('capacity')->default(48)->after('color');
            $table->string('pon_port')->nullable()->after('capacity');
            $table->string('cable_no')->nullable()->after('pon_port');
            $table->string('area')->nullable()->after('cable_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('closures', function (Blueprint $table) {
            $table->dropColumn(['color', 'capacity', 'pon_port', 'cable_no', 'area']);
        });
    }
};
