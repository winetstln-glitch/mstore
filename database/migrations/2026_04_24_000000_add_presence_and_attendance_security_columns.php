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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('avatar');
            }
            if (! Schema::hasColumn('users', 'last_seen_ip')) {
                $table->string('last_seen_ip', 64)->nullable()->after('last_seen_at');
            }
            if (! Schema::hasColumn('users', 'last_seen_user_agent')) {
                $table->string('last_seen_user_agent', 255)->nullable()->after('last_seen_ip');
            }
            if (! Schema::hasColumn('users', 'attendance_device_hash')) {
                $table->string('attendance_device_hash', 128)->nullable()->after('last_seen_user_agent');
            }
            if (! Schema::hasColumn('users', 'attendance_device_locked_at')) {
                $table->timestamp('attendance_device_locked_at')->nullable()->after('attendance_device_hash');
            }
        });

        Schema::table('technician_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('technician_attendances', 'device_fingerprint_clock_in')) {
                $table->string('device_fingerprint_clock_in', 128)->nullable()->after('lng_clock_in');
            }
            if (! Schema::hasColumn('technician_attendances', 'device_fingerprint_clock_out')) {
                $table->string('device_fingerprint_clock_out', 128)->nullable()->after('lng_clock_out');
            }
            if (! Schema::hasColumn('technician_attendances', 'ip_clock_in')) {
                $table->string('ip_clock_in', 64)->nullable()->after('device_fingerprint_clock_in');
            }
            if (! Schema::hasColumn('technician_attendances', 'ip_clock_out')) {
                $table->string('ip_clock_out', 64)->nullable()->after('device_fingerprint_clock_out');
            }
            if (! Schema::hasColumn('technician_attendances', 'user_agent_clock_in')) {
                $table->string('user_agent_clock_in', 255)->nullable()->after('ip_clock_in');
            }
            if (! Schema::hasColumn('technician_attendances', 'user_agent_clock_out')) {
                $table->string('user_agent_clock_out', 255)->nullable()->after('ip_clock_out');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technician_attendances', function (Blueprint $table) {
            foreach ([
                'device_fingerprint_clock_in',
                'device_fingerprint_clock_out',
                'ip_clock_in',
                'ip_clock_out',
                'user_agent_clock_in',
                'user_agent_clock_out',
            ] as $column) {
                if (Schema::hasColumn('technician_attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'last_seen_at',
                'last_seen_ip',
                'last_seen_user_agent',
                'attendance_device_hash',
                'attendance_device_locked_at',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

