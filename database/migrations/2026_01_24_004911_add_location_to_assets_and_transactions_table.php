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
        Schema::table('assets', function (Blueprint $table) {
            $table->string('latitude')->nullable()->after('condition');
            $table->string('longitude')->nullable()->after('latitude');
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->string('latitude')->nullable()->after('description');
            $table->string('longitude')->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
