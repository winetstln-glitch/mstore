<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            if (! Schema::hasColumn('onus', 'olt_port_id')) {
                $table->foreignId('olt_port_id')->nullable()->constrained('olt_ports')->onDelete('set null')->after('olt_id');
            }
            if (! Schema::hasColumn('onus', 'ont_id')) {
                $table->string('ont_id')->after('olt_port_id');
                $table->unique(['olt_id', 'ont_id']);
            }
            if (! Schema::hasColumn('onus', 'vendor')) {
                $table->string('vendor')->nullable()->after('mac_address');
            }
            if (! Schema::hasColumn('onus', 'model')) {
                $table->string('model')->nullable()->after('vendor');
            }
            if (! Schema::hasColumn('onus', 'firmware_version')) {
                $table->string('firmware_version')->nullable()->after('model');
            }
            if (! Schema::hasColumn('onus', 'hardware_version')) {
                $table->string('hardware_version')->nullable()->after('firmware_version');
            }
            if (! Schema::hasColumn('onus', 'password')) {
                $table->string('password')->nullable()->after('hardware_version');
            }
            if (! Schema::hasColumn('onus', 'line_profile')) {
                $table->string('line_profile')->nullable()->after('password');
            }
            if (! Schema::hasColumn('onus', 'service_profile')) {
                $table->string('service_profile')->nullable()->after('line_profile');
            }
            if (! Schema::hasColumn('onus', 'admin_status')) {
                $table->string('admin_status')->default('up')->after('service_profile');
            }
            if (! Schema::hasColumn('onus', 'oper_status')) {
                $table->string('oper_status')->default('offline')->after('admin_status');
            }
            if (! Schema::hasColumn('onus', 'voltage')) {
                $table->decimal('voltage', 8, 2)->nullable()->after('tx_power');
            }
            if (! Schema::hasColumn('onus', 'temperature')) {
                $table->decimal('temperature', 8, 2)->nullable()->after('voltage');
            }
            if (! Schema::hasColumn('onus', 'rtt')) {
                $table->integer('rtt')->nullable()->after('distance');
            }
            if (! Schema::hasColumn('onus', 'rx_bytes')) {
                $table->bigInteger('rx_bytes')->default(0)->after('oper_status');
            }
            if (! Schema::hasColumn('onus', 'tx_bytes')) {
                $table->bigInteger('tx_bytes')->default(0)->after('rx_bytes');
            }
            if (! Schema::hasColumn('onus', 'rx_packets')) {
                $table->bigInteger('rx_packets')->default(0)->after('tx_bytes');
            }
            if (! Schema::hasColumn('onus', 'tx_packets')) {
                $table->bigInteger('tx_packets')->default(0)->after('rx_packets');
            }
            if (! Schema::hasColumn('onus', 'rx_drop_packets')) {
                $table->bigInteger('rx_drop_packets')->default(0)->after('tx_packets');
            }
            if (! Schema::hasColumn('onus', 'tx_drop_packets')) {
                $table->bigInteger('tx_drop_packets')->default(0)->after('rx_drop_packets');
            }
            if (! Schema::hasColumn('onus', 'vlans')) {
                $table->json('vlans')->nullable()->after('tx_drop_packets');
            }
            if (! Schema::hasColumn('onus', 'last_active_at')) {
                $table->timestamp('last_active_at')->nullable()->after('vlans');
            }
            if (! Schema::hasColumn('onus', 'last_polled_at')) {
                $table->timestamp('last_polled_at')->nullable()->after('last_active_at');
            }
            if (! Schema::hasColumn('onus', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            $columnsToDrop = [
                'olt_port_id', 'ont_id', 'vendor', 'model',
                'firmware_version', 'hardware_version', 'password',
                'line_profile', 'service_profile',
                'admin_status', 'oper_status',
                'voltage', 'temperature', 'rtt',
                'rx_bytes', 'tx_bytes', 'rx_packets', 'tx_packets',
                'rx_drop_packets', 'tx_drop_packets',
                'vlans', 'last_active_at', 'last_polled_at',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('onus', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('onus', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
