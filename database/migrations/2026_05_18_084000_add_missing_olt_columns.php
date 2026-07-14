<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            if (! Schema::hasColumn('olts', 'ip_address')) {
                $table->string('ip_address', 45)->unique()->nullable()->after('name');
            }
            if (! Schema::hasColumn('olts', 'vendor')) {
                $table->string('vendor')->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('olts', 'location')) {
                $table->string('location')->nullable()->after('model');
            }
            if (! Schema::hasColumn('olts', 'read_community')) {
                $table->string('read_community', 128)->default('public')->after('location');
            }
            if (! Schema::hasColumn('olts', 'write_community')) {
                $table->string('write_community', 128)->nullable()->after('read_community');
            }
            if (! Schema::hasColumn('olts', 'snmpv3_config')) {
                $table->json('snmpv3_config')->nullable()->after('snmp_version');
            }
            if (! Schema::hasColumn('olts', 'poll_interval')) {
                $table->integer('poll_interval')->default(300)->after('snmpv3_config');
            }
            if (! Schema::hasColumn('olts', 'snmp_timeout')) {
                $table->integer('snmp_timeout')->default(10)->after('poll_interval');
            }
            if (! Schema::hasColumn('olts', 'snmp_retries')) {
                $table->integer('snmp_retries')->default(2)->after('snmp_timeout');
            }
            if (! Schema::hasColumn('olts', 'firmware')) {
                $table->string('firmware')->nullable()->after('snmp_retries');
            }
            if (! Schema::hasColumn('olts', 'serial_number')) {
                $table->string('serial_number')->nullable()->after('firmware');
            }
            if (! Schema::hasColumn('olts', 'mac_address')) {
                $table->string('mac_address', 17)->nullable()->after('serial_number');
            }
            if (! Schema::hasColumn('olts', 'cpu_usage')) {
                $table->integer('cpu_usage')->nullable()->after('mac_address');
            }
            if (! Schema::hasColumn('olts', 'memory_usage')) {
                $table->integer('memory_usage')->nullable()->after('cpu_usage');
            }
            if (! Schema::hasColumn('olts', 'temperature')) {
                $table->integer('temperature')->nullable()->after('memory_usage');
            }
            if (! Schema::hasColumn('olts', 'uptime')) {
                $table->string('uptime')->nullable()->after('temperature');
            }
            if (! Schema::hasColumn('olts', 'status')) {
                $table->string('status')->default('offline')->after('uptime');
            }
            if (! Schema::hasColumn('olts', 'last_polled_at')) {
                $table->timestamp('last_polled_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('olts', 'last_online_at')) {
                $table->timestamp('last_online_at')->nullable()->after('last_polled_at');
            }
            if (! Schema::hasColumn('olts', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('last_online_at');
            }
            if (! Schema::hasColumn('olts', 'custom_oid_name')) {
                $table->string('custom_oid_name')->nullable()->after('last_synced_at');
            }
            if (! Schema::hasColumn('olts', 'custom_oid_status')) {
                $table->string('custom_oid_status')->nullable()->after('custom_oid_name');
            }
            if (! Schema::hasColumn('olts', 'custom_oid_rx')) {
                $table->string('custom_oid_rx')->nullable()->after('custom_oid_status');
            }
            if (! Schema::hasColumn('olts', 'custom_oid_tx')) {
                $table->string('custom_oid_tx')->nullable()->after('custom_oid_rx');
            }
            if (! Schema::hasColumn('olts', 'custom_oid_mac')) {
                $table->string('custom_oid_mac')->nullable()->after('custom_oid_tx');
            }
            if (! Schema::hasColumn('olts', 'custom_oid_sn')) {
                $table->string('custom_oid_sn')->nullable()->after('custom_oid_mac');
            }
            if (! Schema::hasColumn('olts', 'custom_divider')) {
                $table->integer('custom_divider')->nullable()->after('custom_oid_sn');
            }
            if (! Schema::hasColumn('olts', 'api_token')) {
                $table->string('api_token')->nullable()->after('custom_divider');
            }
            if (! Schema::hasColumn('olts', 'last_status')) {
                $table->string('last_status')->nullable()->after('api_token');
            }
            if (! Schema::hasColumn('olts', 'last_status_message')) {
                $table->text('last_status_message')->nullable()->after('last_status');
            }
            if (! Schema::hasColumn('olts', 'last_status_check')) {
                $table->timestamp('last_status_check')->nullable()->after('last_status_message');
            }
            if (! Schema::hasColumn('olts', 'total_onus')) {
                $table->integer('total_onus')->default(0)->after('last_status_check');
            }
            if (! Schema::hasColumn('olts', 'online_onus')) {
                $table->integer('online_onus')->default(0)->after('total_onus');
            }
            if (! Schema::hasColumn('olts', 'offline_onus')) {
                $table->integer('offline_onus')->default(0)->after('online_onus');
            }
            if (! Schema::hasColumn('olts', 'los_onus')) {
                $table->integer('los_onus')->default(0)->after('offline_onus');
            }
            if (! Schema::hasColumn('olts', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $columnsToDrop = [
                'ip_address', 'vendor', 'location', 'read_community', 'write_community',
                'snmpv3_config', 'poll_interval', 'snmp_timeout', 'snmp_retries',
                'firmware', 'serial_number', 'mac_address', 'cpu_usage', 'memory_usage',
                'temperature', 'uptime', 'status', 'last_polled_at', 'last_online_at',
                'last_synced_at', 'custom_oid_name', 'custom_oid_status', 'custom_oid_rx',
                'custom_oid_tx', 'custom_oid_mac', 'custom_oid_sn', 'custom_divider',
                'api_token', 'last_status', 'last_status_message', 'last_status_check',
                'total_onus', 'online_onus', 'offline_onus', 'los_onus',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('olts', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('olts', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
