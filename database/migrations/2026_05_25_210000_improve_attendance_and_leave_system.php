<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->enum('type', ['leave', 'permission', 'sick'])->default('leave')->after('user_id');
            $table->integer('leave_days_used')->default(0)->after('end_date');
            $table->text('document_path')->nullable()->after('reason');
        });

        Schema::table('technician_attendances', function (Blueprint $table) {
            $table->integer('late_minutes')->default(0)->after('status');
            $table->integer('permission_minutes')->default(0)->after('late_minutes');
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete()->after('notes');
            $table->text('edit_reason')->nullable()->after('edited_by');
        });

        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date');
            $table->boolean('is_national')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique('date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('annual_leave_quota')->default(12)->after('is_active');
            $table->integer('annual_leave_used')->default(0)->after('annual_leave_quota');
            $table->integer('sick_leave_quota')->default(12)->after('annual_leave_used');
            $table->integer('sick_leave_used')->default(0)->after('sick_leave_quota');
        });

        Setting::firstOrCreate([
            'key' => 'attendance_late_tolerance_minutes',
            'value' => '15',
            'group' => 'attendance',
            'type' => 'number',
            'label' => 'Late Tolerance (Minutes)',
        ]);

        Setting::firstOrCreate([
            'key' => 'attendance_cutoff_time',
            'value' => '10:00',
            'group' => 'attendance',
            'type' => 'time',
            'label' => 'Attendance Cutoff Time',
        ]);
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['type', 'leave_days_used', 'document_path']);
        });

        Schema::table('technician_attendances', function (Blueprint $table) {
            $table->dropColumn(['late_minutes', 'permission_minutes', 'edited_by', 'edit_reason']);
        });

        Schema::dropIfExists('public_holidays');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['annual_leave_quota', 'annual_leave_used', 'sick_leave_quota', 'sick_leave_used']);
        });

        Setting::whereIn('key', ['attendance_late_tolerance_minutes', 'attendance_cutoff_time'])->delete();
    }
};
