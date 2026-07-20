<?php

namespace App\Actions\Attendance;

use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\TechnicianAttendance;
use App\Models\TechnicianDailySchedule;
use App\Models\TechnicianSchedule;
use App\Models\User;
use App\Services\Attendance\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MarkAbsentAsAlphaAction
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    public function execute(User $user, Carbon $date): ?TechnicianAttendance
    {
        Log::info('MarkAbsentAsAlphaAction: Checking user', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role_name' => $user->role?->name ?? 'N/A',
            'date' => $date->toDateString(),
        ]);

        $autoAlphaEnabled = (bool) Setting::getValue('attendance_auto_alpha_enabled', true);
        if (!$autoAlphaEnabled) {
            Log::info('MarkAbsentAsAlphaAction: Auto alpha disabled, skipping', [
                'user_id' => $user->id,
            ]);
            return null;
        }

        $existingAttendance = TechnicianAttendance::where('user_id', $user->id)
            ->where(function ($query) use ($date) {
                $query->whereDate('clock_in', $date->toDateString())
                      ->orWhereDate('work_date', $date->toDateString());
            })
            ->first();

        if ($existingAttendance) {
            Log::info('MarkAbsentAsAlphaAction: Existing attendance found, skipping', [
                'user_id' => $user->id,
                'attendance_id' => $existingAttendance->id,
            ]);
            return null;
        }

        $approvedLeave = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->first();

        if ($approvedLeave) {
            Log::info('MarkAbsentAsAlphaAction: Approved leave found, skipping', [
                'user_id' => $user->id,
                'leave_id' => $approvedLeave->id,
            ]);
            return null;
        }

        $isExcludedFromSchedule = $user->hasAnyRole([Role::DIREKTUR, Role::COORDINATOR, 'owner', 'owner pendiri', 'owner-pendiri']);
        $eligibleRoles = [
            Role::ADMIN,
            Role::LEADER,
            Role::FINANCE,
            Role::HRD_MANAGER,
            Role::NOC,
            Role::TECHNICIAN,
            Role::KASIR_ATK,
            Role::KASIR_WASH,
            Role::KARYAWAN_WASH,
        ];
        $isEligible = $user->hasAnyRole($eligibleRoles);

        Log::info('MarkAbsentAsAlphaAction: Role check', [
            'user_id' => $user->id,
            'role_name_raw' => $user->role?->name ?? 'N/A',
            'is_eligible' => $isEligible,
            'is_excluded' => $isExcludedFromSchedule,
        ]);

        if (! $isEligible) {
            Log::info('MarkAbsentAsAlphaAction: Role not eligible, skipping', [
                'user_id' => $user->id,
                'role_name' => $user->role?->name ?? 'N/A',
            ]);
            return null;
        }

        if ($isExcludedFromSchedule) {
            Log::info('MarkAbsentAsAlphaAction: Role excluded from schedule, skipping', [
                'user_id' => $user->id,
                'role_name' => $user->role?->name ?? 'N/A',
            ]);
            return null;
        }

        $clockInWindow = $this->attendanceService->resolveClockInWindow($user);
        $cutoff = $clockInWindow['shift_cutoff'];

        $now = Carbon::now();
        if (! $this->attendanceService->isPastCutoffTime($cutoff, $now)) {
            Log::info('MarkAbsentAsAlphaAction: Not past cutoff yet, skipping', [
                'user_id' => $user->id,
                'cutoff' => $cutoff,
                'now' => $now->format('H:i'),
            ]);
            return null;
        }

        $status = $clockInWindow['status'];

        Log::info('MarkAbsentAsAlphaAction: Schedule status check', [
            'user_id' => $user->id,
            'final_status' => $status,
            'cutoff' => $cutoff,
        ]);

        if (! in_array($status, ['piket', 'backup', 'longshift'], true)) {
            Log::info('MarkAbsentAsAlphaAction: Schedule status not eligible, skipping', [
                'user_id' => $user->id,
                'status' => $status,
            ]);
            return null;
        }

        Log::info('MarkAbsentAsAlphaAction: Creating alpha attendance', [
            'user_id' => $user->id,
            'schedule_status' => $status ?? 'no_schedule',
            'cutoff' => $cutoff,
        ]);

        return TechnicianAttendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'status' => 'alpha',
            'generated_type' => 'system_alpha',
            'notes' => 'Auto-marked as alpha - no check-in after cut-off time (' . $cutoff . ')',
        ]);
    }
}
