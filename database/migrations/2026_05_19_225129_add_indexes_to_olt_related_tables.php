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
        Schema::table('olts', function (Blueprint $table) {
            $table->index('status');
            $table->index('is_active');
            $table->index('last_polled_at');
        });

        Schema::table('olt_ports', function (Blueprint $table) {
            $table->index('olt_id');
            $table->index('oper_status');
        });

        Schema::table('onts', function (Blueprint $table) {
            $table->index('olt_id');
            $table->index('olt_port_id');
            $table->index('ont_id');
            $table->index('oper_status');
            $table->index('last_polled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['last_polled_at']);
        });

        Schema::table('olt_ports', function (Blueprint $table) {
            $table->dropIndex(['olt_id']);
            $table->dropIndex(['oper_status']);
        });

        Schema::table('onts', function (Blueprint $table) {
            $table->dropIndex(['olt_id']);
            $table->dropIndex(['olt_port_id']);
            $table->dropIndex(['ont_id']);
            $table->dropIndex(['oper_status']);
            $table->dropIndex(['last_polled_at']);
        });
    }
};
