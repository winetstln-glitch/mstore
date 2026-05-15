<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            if (!Schema::hasColumn('olts', 'web_user')) {
                $table->string('web_user')->default('admin')->after('snmp_port');
            }
            if (!Schema::hasColumn('olts', 'web_password')) {
                $table->string('web_password')->default('admin')->after('web_user');
            }
            if (!Schema::hasColumn('olts', 'model')) {
                $table->string('model')->nullable()->after('brand');
            }
            if (!Schema::hasColumn('olts', 'last_profile')) {
                $table->string('last_profile')->nullable()->after('model');
            }
        });

        Schema::table('onus', function (Blueprint $table) {
            if (!Schema::hasColumn('onus', 'onu_index')) {
                $table->string('onu_index')->nullable()->after('olt_id');
            }
            if (!Schema::hasColumn('onus', 'tx_power')) {
                $table->string('tx_power')->nullable()->after('signal');
            }
            if (!Schema::hasColumn('onus', 'rx_power')) {
                $table->string('rx_power')->nullable()->after('tx_power');
            }
            if (!Schema::hasColumn('onus', 'sn')) {
                $table->string('sn')->nullable()->after('serial_number');
            }
            if (!Schema::hasColumn('onus', 'mac')) {
                $table->string('mac')->nullable()->after('mac_address');
            }
            if (!Schema::hasColumn('onus', 'last_updated')) {
                $table->timestamp('last_updated')->nullable()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            if (Schema::hasColumn('olts', 'web_user')) {
                $table->dropColumn('web_user');
            }
            if (Schema::hasColumn('olts', 'web_password')) {
                $table->dropColumn('web_password');
            }
            if (Schema::hasColumn('olts', 'model')) {
                $table->dropColumn('model');
            }
            if (Schema::hasColumn('olts', 'last_profile')) {
                $table->dropColumn('last_profile');
            }
        });

        Schema::table('onus', function (Blueprint $table) {
            if (Schema::hasColumn('onus', 'onu_index')) {
                $table->dropColumn('onu_index');
            }
            if (Schema::hasColumn('onus', 'tx_power')) {
                $table->dropColumn('tx_power');
            }
            if (Schema::hasColumn('onus', 'rx_power')) {
                $table->dropColumn('rx_power');
            }
            if (Schema::hasColumn('onus', 'sn')) {
                $table->dropColumn('sn');
            }
            if (Schema::hasColumn('onus', 'mac')) {
                $table->dropColumn('mac');
            }
            if (Schema::hasColumn('onus', 'last_updated')) {
                $table->dropColumn('last_updated');
            }
        });
    }
};
