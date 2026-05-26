<?php

namespace App\Actions\Attendance;

use App\Models\LeaveRequest;
use App\Models\TechnicianAttendance;
use App\Models\TechnicianDailySchedule;
use App\Models\TechnicianSchedule;
use App\Models\User;
use App\Models\WashEmployee;
use App\Services\Attendance\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class MarkAbsentAsAlphaAction
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    public function execute(User $user, Carbon $date): ?TechnicianAttendance
    {
        $existingAttendance = TechnicianAttendance::where('user_id', $user->id)
            ->where(function ($query) use ($date) {
                $query->whereDate('clock_in', $date->toDateString())
                      ->orWhereDate('work_date', $date->toDateString());
            })
            ->first();

        if ($existingAttendance) {
            return null;
        }

        $approvedLeave = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->first();

        if ($approvedLeave) {
            return null;
        }

        $roleName = strtolower((string) ($user->role?->name ?? ''));
        $isExcludedFromSchedule = in_array($roleName, ['direktur', 'owner', 'owner-pendiri', 'coordinator'], true);

        if ($isExcludedFromSchedule) {
            return null;
        }

        $group = $this->attendanceService->resolveUserGroup($user);

        $daily = TechnicianDailySchedule::where('user_id', $user->id)
            ->whereDate('date', $date->toDateString())
            ->first();

        $status = $daily?->status;

        if ($status === null) {
            $weekly = TechnicianSchedule::where('user_id', $user->id)
                ->where('year', $date->year)
                ->where('week_number', $date->weekOfYear)
                ->first();
            $status = $weekly?->status;
        }

        if (! in_array($status, ['piket', 'backup', 'longshift'], true)) {
            return null;
        }

        return TechnicianAttendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'status' => 'alpha',
            'generated_type' => 'system_alpha',
            'notes' => 'Auto-marked as alpha - no check-in after cut-off time',
        ]);
    }
}
