<?php
// database/migrations/2024_01_01_000001_create_olts_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel OLT
        if (! Schema::hasTable('olts')) {
            Schema::create('olts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('ip_address', 45)->unique();
                $table->string('vendor'); // hsgq, huawei, zte, fiberhome, nokia
                $table->string('model')->nullable();
                $table->string('location')->nullable();
                $table->string('read_community', 128)->default('public');
                $table->string('write_community', 128)->nullable();
                $table->string('snmp_version', 4)->default('v2c');
                $table->json('snmpv3_config')->nullable();
                $table->integer('poll_interval')->default(300);
                $table->integer('snmp_timeout')->default(10);
                $table->integer('snmp_retries')->default(2);
                $table->string('firmware')->nullable();
                $table->string('serial_number')->nullable();
                $table->string('mac_address', 17)->nullable();
                $table->integer('cpu_usage')->nullable();
                $table->integer('memory_usage')->nullable();
                $table->integer('temperature')->nullable();
                $table->string('uptime')->nullable();
                $table->string('status')->default('offline');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_polled_at')->nullable();
                $table->timestamp('last_online_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Tabel Port OLT
        if (! Schema::hasTable('olt_ports')) {
            Schema::create('olt_ports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('olt_id')->constrained()->onDelete('cascade');
                $table->string('name'); // PON01, GE01, XGE01
                $table->string('type')->default('pon');
                $table->integer('index_number')->nullable();
                $table->string('admin_status')->default('up');
                $table->string('oper_status')->default('down');
                $table->bigInteger('rx_bytes')->default(0);
                $table->bigInteger('tx_bytes')->default(0);
                $table->bigInteger('rx_packets')->default(0);
                $table->bigInteger('tx_packets')->default(0);
                $table->bigInteger('rx_errors')->default(0);
                $table->bigInteger('tx_errors')->default(0);
                $table->integer('speed')->nullable();
                $table->integer('max_onts')->default(128);
                $table->integer('registered_onts')->default(0);
                $table->json('optical_info')->nullable();
                $table->timestamps();
            });
        }

        // Tabel ONT / ONU
        if (! Schema::hasTable('onts')) {
            Schema::create('onts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('olt_id')->constrained()->onDelete('cascade');
                $table->foreignId('olt_port_id')->nullable()->constrained('olt_ports')->onDelete('set null');
                $table->string('ont_id'); // ONT01/000, atau serial
                $table->string('name')->nullable(); // Nama pelanggan
                $table->string('serial_number')->nullable();
                $table->string('mac_address', 17)->nullable();
                $table->string('vendor')->nullable(); // ZTEG, HWTC, FHTT
                $table->string('model')->nullable();
                $table->string('firmware_version')->nullable();
                $table->string('hardware_version')->nullable();
                $table->string('password')->nullable(); // LOID
                $table->string('line_profile')->nullable();
                $table->string('service_profile')->nullable();
                $table->string('admin_status')->default('up');
                $table->string('oper_status')->default('offline');
                $table->decimal('rx_power', 8, 2)->nullable();
                $table->decimal('tx_power', 8, 2)->nullable();
                $table->decimal('voltage', 8, 2)->nullable();
                $table->decimal('temperature', 8, 2)->nullable();
                $table->integer('distance')->nullable();
                $table->integer('rtt')->nullable();
                $table->bigInteger('rx_bytes')->default(0);
                $table->bigInteger('tx_bytes')->default(0);
                $table->bigInteger('rx_packets')->default(0);
                $table->bigInteger('tx_packets')->default(0);
                $table->bigInteger('rx_drop_packets')->default(0);
                $table->bigInteger('tx_drop_packets')->default(0);
                $table->json('vlans')->nullable();
                $table->timestamp('last_active_at')->nullable();
                $table->timestamp('last_polled_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->unique(['olt_id', 'ont_id']);
            });
        }

        // Riwayat Optik
        if (! Schema::hasTable('ont_optical_history')) {
            Schema::create('ont_optical_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ont_id')->constrained()->onDelete('cascade');
                $table->decimal('rx_power', 8, 2)->nullable();
                $table->decimal('tx_power', 8, 2)->nullable();
                $table->decimal('voltage', 8, 2)->nullable();
                $table->decimal('temperature', 8, 2)->nullable();
                $table->timestamp('recorded_at');
                $table->index(['ont_id', 'recorded_at']);
            });
        }

        // Riwayat Traffic
        if (! Schema::hasTable('ont_traffic_history')) {
            Schema::create('ont_traffic_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ont_id')->constrained()->onDelete('cascade');
                $table->bigInteger('rx_bytes')->default(0);
                $table->bigInteger('tx_bytes')->default(0);
                $table->bigInteger('rx_packets')->default(0);
                $table->bigInteger('tx_packets')->default(0);
                $table->timestamp('recorded_at');
                $table->index(['ont_id', 'recorded_at']);
            });
        }

        // Alarm
        if (! Schema::hasTable('alarms')) {
            Schema::create('alarms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('olt_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('ont_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('severity'); // critical, major, minor, warning, info
                $table->string('type'); // link_down, rx_power_low, dying_gasp, temp_high
                $table->string('message');
                $table->json('details')->nullable();
                $table->boolean('acknowledged')->default(false);
                $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('acknowledged_at')->nullable();
                $table->boolean('resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();
                $table->index(['olt_id', 'severity', 'resolved']);
            });
        }

        // Log Polling
        if (! Schema::hasTable('polling_logs')) {
            Schema::create('polling_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('olt_id')->constrained()->onDelete('cascade');
                $table->string('status'); // success, failed, timeout, partial
                $table->integer('duration_ms')->nullable();
                $table->integer('onts_found')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamp('polled_at');
                $table->index(['olt_id', 'polled_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_logs');
        Schema::dropIfExists('alarms');
        Schema::dropIfExists('ont_traffic_history');
        Schema::dropIfExists('ont_optical_history');
        Schema::dropIfExists('onts');
        Schema::dropIfExists('olt_ports');
        Schema::dropIfExists('olts');
    }
};