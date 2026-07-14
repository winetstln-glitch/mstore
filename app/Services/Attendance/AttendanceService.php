<?php

namespace App\Services\Attendance;

use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\TechnicianAttendance;
use App\Models\User;
use App\Models\WashEmployee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AttendanceService
{
    public function __construct() {}

    public function resolveClockInWindow(User $user): array
    {
        $group = $this->resolveUserGroup($user);
        $roleName = strtolower((string) ($user->role?->name ?? ''));
        $isExcludedFromSchedule = in_array($roleName, ['admin', 'leader', 'owner', 'owner-pendiri', 'direktur', 'coordinator'], true);

        $status = null;
        $today = Carbon::today();

        if (! $isExcludedFromSchedule) {
            $daily = \App\Models\TechnicianDailySchedule::where('user_id', $user->id)
                ->whereDate('date', $today->toDateString())
                ->first();
            if ($daily) {
                $status = $daily->status;
            }

            if ($status === null) {
                $weekly = \App\Models\TechnicianSchedule::where('user_id', $user->id)
                    ->where('year', $today->year)
                    ->where('week_number', $today->weekOfYear)
                    ->first();
                if ($weekly) {
                    $status = $weekly->status;
                }
            }
        }

        if (! in_array($status, ['piket', 'backup', 'longshift', 'off'], true)) {
            $status = 'piket';
        }

        $shiftConfig = [
            'shift_1_start' => Setting::getValue($group === 'wash' ? 'schedule_wash_shift_1_start' : 'schedule_teknisi_shift_1_start', '08:00'),
            'shift_1_end' => Setting::getValue($group === 'wash' ? 'schedule_wash_shift_1_end' : 'schedule_teknisi_shift_1_end', '17:00'),
            'shift_2_start' => Setting::getValue($group === 'wash' ? 'schedule_wash_shift_2_start' : 'schedule_teknisi_shift_2_start', '15:00'),
            'shift_2_end' => Setting::getValue($group === 'wash' ? 'schedule_wash_shift_2_end' : 'schedule_teknisi_shift_2_end', '00:00'),
            'longshift_start' => Setting::getValue($group === 'wash' ? 'schedule_wash_longshift_start' : 'schedule_teknisi_longshift_start', '08:00'),
            'longshift_end' => Setting::getValue($group === 'wash' ? 'schedule_wash_longshift_end' : 'schedule_teknisi_longshift_end', '20:00'),
            'official_start' => Setting::getValue($group === 'wash' ? 'schedule_wash_official_start' : 'schedule_teknisi_official_start', '08:00'),
            'shift_cutoff' => Setting::getValue($group === 'wash' ? 'schedule_wash_shift_cutoff' : 'schedule_teknisi_shift_cutoff', '10:00'),
        ];

        $shiftStart = $shiftConfig['shift_1_start'];
        $shiftEnd = $shiftConfig['shift_1_end'];

        if ($status === 'longshift') {
            $shiftStart = $shiftConfig['longshift_start'];
            $shiftEnd = $shiftConfig['longshift_end'];
        } elseif ($status === 'piket') {
            $settingKey = $group === 'wash' ? 'weekly_schedule_wash' : 'weekly_schedule_teknisi';
            $scheduleRaw = (string) Setting::getValue($settingKey, '{}');
            $schedule = json_decode($scheduleRaw, true);
            $dayName = $today->englishDayOfWeek;

            $dayConfig = $schedule[$dayName] ?? null;
            $isShiftEnabled = !empty($dayConfig['enabled']);
            $mappedShift = $isShiftEnabled ? ($dayConfig['shift'] ?? 'shift1') : 'shift1';

            if ($mappedShift === 'longshift') {
                $shiftStart = $shiftConfig['longshift_start'];
                $shiftEnd = $shiftConfig['longshift_end'];
            } elseif ($mappedShift === 'shift2') {
                $shiftStart = $shiftConfig['shift_2_start'];
                $shiftEnd = $shiftConfig['shift_2_end'];
            }
        } elseif ($status === 'backup') {
            $shiftStart = $shiftConfig['shift_2_start'];
            $shiftEnd = $shiftConfig['shift_2_end'];
        }

        return [
            'start' => $shiftStart,
            'end' => $shiftEnd,
            'official_start' => $shiftConfig['official_start'],
            'shift_cutoff' => $shiftConfig['shift_cutoff'],
            'status' => $status,
        ];
    }

    public function determineClockInStatus(string $clockInStart, string $shiftCutoff, ?Carbon $now = null): string
    {
        $checkTime = ($now ?? now())->copy()->timezone(config('app.timezone', 'Asia/Jakarta'));
        $lateTolerance = (int) Setting::getValue('attendance_late_tolerance', 0);
        $lateTolerance = max(0, $lateTolerance);

        $clockInStartMinutes = $this->timeToMinutes($clockInStart, 8 * 60);
        $currentMinutes = ((int) $checkTime->format('H') * 60) + (int) $checkTime->format('i');
        $lateThreshold = min((23 * 60) + 59, $clockInStartMinutes + $lateTolerance);

        return $currentMinutes > $lateThreshold ? 'late' : 'present';
    }

    public function isPastCutoffTime(string $shiftCutoff, ?Carbon $now = null): bool
    {
        $checkTime = ($now ?? now())->copy()->timezone(config('app.timezone', 'Asia/Jakarta'));
        $currentMinutes = ((int) $checkTime->format('H') * 60) + (int) $checkTime->format('i');
        $cutoffMinutes = $this->timeToMinutes($shiftCutoff, 10 * 60);
        return $currentMinutes > $cutoffMinutes;
    }

    public function isTimeWithinRange(string $currentTime, string $startTime, string $endTime): bool
    {
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        }
        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function timeToMinutes(string $time, int $default = 0): int
    {
        $parts = explode(':', $time);
        if (count($parts) < 2) {
            return $default;
        }
        return ((int) $parts[0] * 60) + (int) $parts[1];
    }

    public function resolveUserGroup(User $user): string
    {
        $roleName = strtolower((string) ($user->role?->name ?? ''));
        if (in_array($roleName, ['kasir-wash', 'karyawan-wash'], true)) {
            return 'wash';
        }
        static $washEmployeesCache = null;
        if ($washEmployeesCache === null) {
            $washEmployeesCache = Schema::hasTable('wash_employees')
                ? WashEmployee::pluck('user_id')->all()
                : [];
        }
        if (in_array($user->id, $washEmployeesCache)) {
            return 'wash';
        }
        return 'teknisi';
    }

    public function isAttendanceEligibleUser(User $user): bool
    {
        if (! $user->role) {
            return false;
        }
        
        $excludedRoles = ['customer', 'reseller', 'coordinator', 'direktur', 'owner', 'owner pendiri', 'owner-pendiri'];
        return ! $user->hasAnyRole($excludedRoles);
    }

    public function isUserCoordinator(User $user): bool
    {
        if (! $user->role) {
            return false;
        }
        return $user->hasRole(Role::COORDINATOR);
    }

    public function canViewAllAttendanceData(User $user): bool
    {
        if (!$user || !$user->role) {
            return false;
        }
        
        return $user->hasAnyRole(['admin', 'finance', 'direktur', Role::HRD_MANAGER, 'owner', 'owner pendiri', 'leader']);
    }

    public function resolveAttendanceDeviceFingerprint($request): string
    {
        $fingerprint = $request->device_fingerprint ?? null;
        if (! $fingerprint || strlen($fingerprint) < 8) {
            $fingerprint = hash('sha256', ($request->ip() ?? 'unknown') . '|' . ($request->userAgent() ?? 'unknown'));
        }
        return mb_substr($fingerprint, 0, 128);
    }

    public function resolveAttendancePhotoMaxKb(User $user): int
    {
        $group = $this->resolveUserGroup($user);
        $settingKey = $group === 'wash'
            ? 'attendance_photo_max_kb_wash'
            : 'attendance_photo_max_kb';
        return (int) Setting::getValue($settingKey, 2048);
    }

    public function hasApprovedLeave(User $user, Carbon $date): bool
    {
        return LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->exists();
    }

    public function getTodayAttendance(User $user): ?TechnicianAttendance
    {
        $today = Carbon::today();
        return TechnicianAttendance::where('user_id', $user->id)
            ->where(function ($query) use ($today) {
                $query->whereDate('clock_in', $today->toDateString())
                      ->orWhereDate('work_date', $today->toDateString());
            })
            ->first();
    }

    public function resolveAttendanceUser(string $cardCode): ?User
    {
        return User::whereHas('role', function ($q) {
            $q->whereNotIn('name', [Role::CUSTOMER, Role::COORDINATOR]);
        })
        ->where('is_active', true)
        ->where(function ($q) use ($cardCode) {
            $q->where('card_code', $cardCode)
              ->orWhere('id_card_number', $cardCode)
              ->orWhere('id', (int) $cardCode);
        })
        ->first();
    }

    public function attendanceRedirectRoute($request): string
    {
        if ($request->has('from') && $request->from === 'landing') {
            return 'landing';
        }
        return 'dashboard';
    }

    public function isAdminOrHrdManager(User $user): bool
    {
        if (!$user || !$user->role) {
            return false;
        }
        
        return $user->hasAnyRole(['admin', 'direktur', Role::HRD_MANAGER, 'owner', 'owner pendiri']);
    }
}
