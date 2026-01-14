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
        Schema::create('technician_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('week_number'); // 1-53
            $table->integer('year');
            $table->enum('status', ['piket', 'off', 'backup'])->default('piket');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Prevent duplicate schedule for same user in same week
            $table->unique(['user_id', 'week_number', 'year']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // Add default setting for leave quota
        Setting::create([
            'key' => 'technician_leave_quota',
            'value' => '3',
            'group' => 'attendance',
            'type' => 'number',
            'label' => 'Monthly Leave Quota (Days)',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('technician_schedules');
        Setting::where('key', 'technician_leave_quota')->delete();
    }
};
