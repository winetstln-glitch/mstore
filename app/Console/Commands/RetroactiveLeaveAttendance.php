<?php

namespace App\Console\Commands;

use App\Models\LeaveRequest;
use App\Models\TechnicianAttendance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RetroactiveLeaveAttendance extends Command
{
    protected $signature = 'attendance:retroactive-leave';
    protected $description = 'Create retroactive technician attendance records for approved leave requests';

    public function handle()
    {
        $this->info('🔍 Looking for approved leave requests without attendance records...');

        $leaveRequests = LeaveRequest::where('status', 'approved')->get();
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($leaveRequests as $leaveRequest) {
            $start = Carbon::parse($leaveRequest->start_date);
            $end = Carbon::parse($leaveRequest->end_date);

            // Determine attendance status based on leave type
            $attendanceStatus = match($leaveRequest->type) {
                'sick' => 'sick',
                'permission' => 'permit',
                default => 'leave',
            };

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                // Check if attendance already exists for this user and date
                $exists = TechnicianAttendance::where('user_id', $leaveRequest->user_id)
                    ->where(function ($q) use ($date) {
                        $q->whereDate('clock_in', $date->toDateString())
                          ->orWhereDate('work_date', $date->toDateString());
                    })
                    ->exists();

                if (! $exists) {
                    // Create attendance entry
                    TechnicianAttendance::create([
                        'user_id' => $leaveRequest->user_id,
                        'work_date' => $date->toDateString(),
                        'clock_in' => $date->toDateString() . ' 08:00:00',
                        'clock_out' => $date->toDateString() . ' 17:00:00',
                        'status' => $attendanceStatus,
                        'notes' => ucfirst($attendanceStatus) . ' otomatis dari pengajuan cuti #' . $leaveRequest->id . ' (retroaktif)',
                        'generated_type' => 'leave_request',
                    ]);
                    $this->line("✅ Created attendance for user ID {$leaveRequest->user_id} on {$date->toDateString()}");
                    $createdCount++;
                } else {
                    $this->line("⏭️ Skipped user ID {$leaveRequest->user_id} on {$date->toDateString()} (already exists)");
                    $skippedCount++;
                }
            }
        }

        $this->newLine();
        $this->info("✅ Done! Created {$createdCount} attendance records, skipped {$skippedCount} existing ones.");
    }
}
