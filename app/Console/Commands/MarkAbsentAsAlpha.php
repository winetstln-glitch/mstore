<?php

namespace App\Console\Commands;

use App\Models\LeaveRequest;
use App\Models\TechnicianAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarkAbsentAsAlpha extends Command
{
    protected $signature = 'attendance:mark-alpha';
    protected $description = 'Mark users who didn\'t check in after cut-off time as alpha';

    public function handle()
    {
        $this->info('Starting to mark absent users as alpha...');

        $users = User::whereHas('role', function ($q) {
            $q->where('name', '!=', 'customer');
        })->where('is_active', true)->get();

        $today = Carbon::today();

        foreach ($users as $user) {
            $existingAttendance = TechnicianAttendance::where('user_id', $user->id)
                ->whereDate('clock_in', $today->toDateString())
                ->first();

            if ($existingAttendance) {
                continue;
            }

            $approvedLeave = LeaveRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today->toDateString())
                ->whereDate('end_date', '>=', $today->toDateString())
                ->first();

            if ($approvedLeave) {
                continue;
            }

            $roleName = strtolower((string) ($user->role?->name ?? ''));
            $isExcludedFromSchedule = in_array($roleName, ['admin', 'leader', 'owner', 'owner-pendiri', 'direktur', 'coordinator'], true);

            if ($isExcludedFromSchedule) {
                continue;
            }

            $group = 'teknisi';
            if (in_array($roleName, ['kasir-wash', 'karyawan-wash'], true)) {
                $group = 'wash';
            } elseif (\Schema::hasTable('wash_employees') && \App\Models\WashEmployee::where('user_id', $user->id)->exists()) {
                $group = 'wash';
            }

            $daily = \App\Models\TechnicianDailySchedule::where('user_id', $user->id)
                ->whereDate('date', $today->toDateString())
                ->first();

            $status = $daily?->status;

            if ($status === null) {
                $weekly = \App\Models\TechnicianSchedule::where('user_id', $user->id)
                    ->where('year', $today->year)
                    ->where('week_number', $today->weekOfYear)
                    ->first();
                $status = $weekly?->status;
            }

            if (! in_array($status, ['piket', 'backup', 'longshift'], true)) {
                continue;
            }

            TechnicianAttendance::create([
                'user_id' => $user->id,
                'clock_in' => $today->setTime(23, 59, 59),
                'status' => 'alpha',
                'notes' => 'Auto-marked as alpha - no check-in after cut-off time',
            ]);

            $this->info("Marked user {$user->name} as alpha for today.");
        }

        $this->info('Process completed!');
        return 0;
    }
}
