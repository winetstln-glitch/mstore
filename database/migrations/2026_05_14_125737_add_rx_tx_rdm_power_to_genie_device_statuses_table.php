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
        Schema::table('genie_device_statuses', function (Blueprint $table) {
            $table->string('rx_power')->nullable()->after('connection_request_url');
            $table->string('tx_power')->nullable()->after('rx_power');
            $table->string('rdm_power')->nullable()->after('tx_power');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genie_device_statuses', function (Blueprint $table) {
            $table->dropColumn(['rx_power', 'tx_power', 'rdm_power']);
        });
    }
};
