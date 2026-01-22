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
        Setting::create([
            'key' => 'attendance_face_verification',
            'value' => '0', // Disabled by default to simplify for users with poor cameras
            'group' => 'attendance',
            'type' => 'boolean',
            'label' => 'Strict Face Verification',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('key', 'attendance_face_verification')->delete();
    }
};
