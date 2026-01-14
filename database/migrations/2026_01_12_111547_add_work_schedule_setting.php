<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultSchedule = [
            'Monday' => ['enabled' => true, 'start' => '08:00', 'end' => '17:00'],
            'Tuesday' => ['enabled' => true, 'start' => '08:00', 'end' => '17:00'],
            'Wednesday' => ['enabled' => true, 'start' => '08:00', 'end' => '17:00'],
            'Thursday' => ['enabled' => true, 'start' => '08:00', 'end' => '17:00'],
            'Friday' => ['enabled' => true, 'start' => '08:00', 'end' => '17:00'],
            'Saturday' => ['enabled' => false, 'start' => '09:00', 'end' => '12:00'],
            'Sunday' => ['enabled' => false, 'start' => '00:00', 'end' => '00:00'],
        ];

        Setting::create([
            'key' => 'work_schedule',
            'value' => json_encode($defaultSchedule),
            'group' => 'attendance',
            'type' => 'schedule_weekly',
            'label' => 'Weekly Work Schedule',
        ]);

        Setting::create([
            'key' => 'attendance_radius',
            'value' => '100', // meters
            'group' => 'attendance',
            'type' => 'number',
            'label' => 'Allowed Radius (meters)',
        ]);
        
        Setting::create([
            'key' => 'attendance_late_tolerance',
            'value' => '15', // minutes
            'group' => 'attendance',
            'type' => 'number',
            'label' => 'Late Tolerance (minutes)',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('key', 'work_schedule')->delete();
        Setting::where('key', 'attendance_radius')->delete();
        Setting::where('key', 'attendance_late_tolerance')->delete();
    }
};
