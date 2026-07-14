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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('device_model')->nullable()->after('onu_serial');
            $table->string('ssid_name')->nullable()->after('device_model');
            $table->string('ssid_password')->nullable()->after('ssid_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['device_model', 'ssid_name', 'ssid_password']);
        });
    }
};
