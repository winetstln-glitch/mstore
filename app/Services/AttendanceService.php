<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\TechnicianDailySchedule;
use App\Models\TechnicianSchedule;
use App\Models\User;
use App\Models\WashEmployee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AttendanceService
{
    public function canViewAllAttendanceData(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'finance', 'direktur', 'owner', 'owner pendiri', 'leader']);
    }

    public function isAdminOrHrdManager(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'manager hrd']);
    }

    public function isUserCoordinator(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasRole(Role::COORDINATOR);
    }

    public function isAttendanceEligibleUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isUserCoordinator($user)) {
            return false;
        }

        $excludedRoles = [Role::CUSTOMER, Role::DIREKTUR, 'owner', Role::COORDINATOR];
        return !$user->hasAnyRole($excludedRoles);
    }

    public function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000;

        $lat1 = deg2rad((float)$lat1);
        $lon1 = deg2rad((float)$lon1);
        $lat2 = deg2rad((float)$lat2);
        $lon2 = deg2rad((float)$lon2);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function attendanceRedirectRoute(Request $request): string
    {
        return $request->routeIs('landing.attendance.*') ? 'landing' : 'attendance.create';
    }

    public function resolveAttendancePhotoMaxKb(): int
    {
        $maxKb = (int) Setting::getValue('attendance_photo_max_kb', 5120);
        if ($maxKb < 256) {
            return 256;
        }
        if ($maxKb > 20480) {
            return 20480;
        }

        return $maxKb;
    }

    public function isAttendancePhotoRequired(): bool
    {
        $value = Setting::getValue('attendance_photo_required', '1');

        return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on'], true);
    }

    public function resolveAttendanceDeviceFingerprint(Request $request, ?User $user = null): string
    {
        $rawFingerprint = trim((string)$request->input('device_fingerprint', ''));
        if ($rawFingerprint !== '') {
            return $rawFingerprint;
        }

        $fallbackPayload = implode('|', [
            (string)($user?->id ?? ''),
            mb_substr((string)$request->userAgent(), 0, 255),
        ]);

        return hash('sha256', $fallbackPayload);
    }

    public function resolveAttendanceUser(string $cardCode): ?User
    {
        $code = trim($cardCode);
        if ($code === '') {
            return null;
        }

        return User::query()
            ->where('is_active', true)
            ->where(function ($q) use ($code) {
                $q->where('attendance_card_code', $code)
                    ->orWhere('username', $code)
                    ->orWhere('radius_username', $code);
                if (ctype_digit($code)) {
                    $q->orWhere('id', (int)$code);
                }
            })
            ->first();
    }

    public function isTimeWithinRange(string $currentTime, string $startTime, string $endTime): bool
    {
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        }

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    public function subtractMinutesFromTime(string $time, int $minutes): string
    {
        if ($minutes <= 0) {
            return $time;
        }

        try {
            $base = now()->copy()->startOfDay()->setTimeFromTimeString($time);
            $reduced = $base->copy()->subMinutes($minutes);
            if ($reduced->lt($base->copy()->startOfDay())) {
                return '00:00';
            }

            return $reduced->format('H:i');
        } catch (\Throwable $e) {
            return $time;
        }
    }

    public function timeToMinutes(string $time, int $default): int
    {
        $time = preg_replace('/[^0-9:]/', '', $time);

        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            return $default;
        }

        $hours = (int)$matches[1];
        $minutes = (int)$matches[2];

        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return $default;
        }

        return ($hours * 60) + $minutes;
    }

    public function resolveClockInWindow(User $user): array
    {
        $globalStart = Setting::getValue('attendance_clock_in_start', '07:00');
        $globalEnd = Setting::getValue('attendance_clock_in_end', '13:00');
        $earlyMinutes = (int)Setting::getValue('attendance_clock_in_early_minutes', 60);
        if ($earlyMinutes < 0) {
            $earlyMinutes = 0;
        }
        $shiftInfo = $this->resolveTodayShiftInfo($user);

        $isWorkShift = in_array($shiftInfo['status'] ?? '', [
            TechnicianSchedule::STATUS_PIKET,
            TechnicianSchedule::STATUS_BACKUP,
            TechnicianSchedule::STATUS_LONGSHIFT,
        ], true);
        $hasShiftTime = !empty($shiftInfo['shift_start']) && $shiftInfo['shift_start'] !== '-';

        if ($isWorkShift && $hasShiftTime) {
            $officialStart = (string)$shiftInfo['shift_start'];
            $effectiveStart = $this->subtractMinutesFromTime($officialStart, $earlyMinutes);
            $shiftCutoff = (string)($shiftInfo['shift_cutoff'] ?? $globalEnd);

            return [
                'start' => $effectiveStart,
                'end' => (string)($shiftInfo['shift_end'] ?? $globalEnd),
                'official_start' => $officialStart,
                'shift_cutoff' => $shiftCutoff,
            ];
        }

        $globalStartText = (string)$globalStart;
        return [
            'start' => $this->subtractMinutesFromTime($globalStartText, $earlyMinutes),
            'end' => (string)$globalEnd,
            'official_start' => $globalStartText,
            'shift_cutoff' => $globalEnd,
        ];
    }

    public function determineClockInStatus(string $clockInStart, string $shiftCutoff, ?Carbon $now = null): string
    {
        $checkTime = ($now ?? now())->copy()->timezone(config('app.timezone', 'Asia/Jakarta'));
        $lateTolerance = (int)Setting::getValue('attendance_late_tolerance', 0);
        $lateTolerance = max(0, $lateTolerance);

        $clockInStartMinutes = $this->timeToMinutes($clockInStart, 8 * 60);
        $currentMinutes = ((int)$checkTime->format('H') * 60) + (int)$checkTime->format('i');
        $lateThreshold = min((23 * 60) + 59, $clockInStartMinutes + $lateTolerance);

        return $currentMinutes > $lateThreshold ? 'late' : 'present';
    }

    public function isPastCutoffTime(string $shiftCutoff, ?Carbon $now = null): bool
    {
        $checkTime = ($now ?? now())->copy()->timezone(config('app.timezone', 'Asia/Jakarta'));
        $currentMinutes = ((int)$checkTime->format('H') * 60) + (int)$checkTime->format('i');
        $cutoffMinutes = $this->timeToMinutes($shiftCutoff, 10 * 60);
        return $currentMinutes > $cutoffMinutes;
    }

    public function resolveTodayShiftInfo(User $user): array
    {
        $group = $this->resolveScheduleGroup($user);
        $status = null;
        $source = 'default';
        $today = now();

        $roleName = strtolower((string)($user->role?->name ?? ''));
        $isExcludedFromSchedule = in_array($roleName, [Role::DIREKTUR, Role::COORDINATOR], true);

        if (!$isExcludedFromSchedule) {
            if (Schema::hasTable('technician_daily_schedules')) {
                $daily = TechnicianDailySchedule::query()
                    ->where('user_id', $user->id)
                    ->whereDate('date', $today->toDateString())
                    ->first();
                if ($daily) {
                    $status = (string)$daily->status;
                    $source = 'daily';
                }
            }

            if ($status === null && Schema::hasTable('technician_schedules')) {
                $weekYear = (int)$today->copy()->weekYear;
                $weekly = TechnicianSchedule::query()
                    ->where('user_id', $user->id)
                    ->where('year', $weekYear)
                    ->where('week_number', $today->weekOfYear)
                    ->first();
                if ($weekly) {
                    $status = (string)$weekly->status;
                    $source = 'weekly';
                }
            }
        }

        if (!in_array($status, [
            TechnicianSchedule::STATUS_PIKET,
            TechnicianSchedule::STATUS_BACKUP,
            TechnicianSchedule::STATUS_LONGSHIFT,
            TechnicianSchedule::STATUS_OFF,
        ], true)) {
            $status = TechnicianSchedule::STATUS_PIKET;
            $source = 'default';
        }

        $shiftConfig = $this->attendanceShiftConfig($group);
        $shiftLabel = '-';
        $shiftStart = '-';
        $shiftEnd = '-';
        $shiftCutoff = '-';

        if ($status === TechnicianSchedule::STATUS_LONGSHIFT) {
            $shiftLabel = 'Longshift';
            $shiftStart = (string)$shiftConfig['longshift_start'];
            $shiftEnd = (string)$shiftConfig['longshift_end'];
            $shiftCutoff = (string)$shiftConfig['longshift_cutoff'];
        } elseif ($status === TechnicianSchedule::STATUS_PIKET) {
            $settingKey = $group === 'wash' ? 'weekly_schedule_wash' : 'weekly_schedule_teknisi';
            $scheduleRaw = (string)Setting::getValue($settingKey, '{}');
            $schedule = json_decode($scheduleRaw, true);
            $dayName = $today->englishDayOfWeek;

            $dayConfig = $schedule[$dayName] ?? null;
            $isShiftEnabled = !empty($dayConfig['enabled']);
            $mappedShift = $isShiftEnabled ? ($dayConfig['shift'] ?? 'shift1') : 'shift1';

            if ($mappedShift === 'longshift') {
                $shiftLabel = 'Longshift';
                $shiftStart = (string)$shiftConfig['longshift_start'];
                $shiftEnd = (string)$shiftConfig['longshift_end'];
                $shiftCutoff = (string)$shiftConfig['longshift_cutoff'];
            } elseif ($mappedShift === 'shift2') {
                $shiftLabel = 'Shift 2';
                $shiftStart = (string)$shiftConfig['shift_2_start'];
                $shiftEnd = (string)$shiftConfig['shift_2_end'];
                $shiftCutoff = (string)$shiftConfig['shift_2_cutoff'];
            } else {
                $shiftLabel = 'Shift 1';
                $shiftStart = (string)$shiftConfig['shift_1_start'];
                $shiftEnd = (string)$shiftConfig['shift_1_end'];
                $shiftCutoff = (string)$shiftConfig['shift_1_cutoff'];
            }
        } elseif ($status === TechnicianSchedule::STATUS_BACKUP) {
            $shiftLabel = 'Shift 2';
            $shiftStart = (string)$shiftConfig['shift_2_start'];
            $shiftEnd = (string)$shiftConfig['shift_2_end'];
            $shiftCutoff = (string)$shiftConfig['shift_2_cutoff'];
        }

        return [
            'group_label' => $group === 'wash' ? 'Operator Wash' : 'Teknisi',
            'status' => $status,
            'status_label' => match ($status) {
                TechnicianSchedule::STATUS_PIKET => 'Piket',
                TechnicianSchedule::STATUS_BACKUP => 'Backup',
                TechnicianSchedule::STATUS_LONGSHIFT => 'Longshift',
                default => 'Off',
            },
            'shift_label' => $shiftLabel,
            'shift_start' => $shiftStart,
            'shift_end' => $shiftEnd,
            'shift_cutoff' => $shiftCutoff,
            'source' => $source,
        ];
    }

    public function isUserOffOnDate(User $user, string $dateStr): bool
    {
        $date = Carbon::parse($dateStr);
        $roleName = strtolower((string)($user->role?->name ?? ''));
        $isExcludedFromSchedule = in_array($roleName, [Role::DIREKTUR, Role::COORDINATOR], true);

        if ($isExcludedFromSchedule) {
            return false;
        }

        $status = null;

        if (Schema::hasTable('technician_daily_schedules')) {
            $daily = TechnicianDailySchedule::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $date->toDateString())
                ->first();
            if ($daily) {
                $status = (string)$daily->status;
            }
        }

        if ($status === null && Schema::hasTable('technician_schedules')) {
            $weekYear = (int)$date->copy()->weekYear;
            $weekly = TechnicianSchedule::query()
                ->where('user_id', $user->id)
                ->where('year', $weekYear)
                ->where('week_number', $date->weekOfYear)
                ->first();
            if ($weekly) {
                $status = (string)$weekly->status;
            }
        }

        return $status === TechnicianSchedule::STATUS_OFF;
    }

    public function attendanceShiftConfig(string $group): array
    {
        if ($group === 'wash') {
            return [
                'shift_1_start' => Setting::getValue('schedule_wash_shift_1_start', '08:00'),
                'shift_1_end' => Setting::getValue('schedule_wash_shift_1_end', '17:00'),
                'shift_1_cutoff' => Setting::getValue('schedule_wash_shift_1_cutoff', '13:00'),
                'shift_2_start' => Setting::getValue('schedule_wash_shift_2_start', '13:00'),
                'shift_2_end' => Setting::getValue('schedule_wash_shift_2_end', '22:00'),
                'shift_2_cutoff' => Setting::getValue('schedule_wash_shift_2_cutoff', '17:00'),
                'longshift_start' => Setting::getValue('schedule_wash_longshift_start', '08:00'),
                'longshift_end' => Setting::getValue('schedule_wash_longshift_end', '20:00'),
                'longshift_cutoff' => Setting::getValue('schedule_wash_longshift_cutoff', '13:00'),
            ];
        }

        return [
            'shift_1_start' => Setting::getValue('schedule_teknisi_shift_1_start', '08:00'),
            'shift_1_end' => Setting::getValue('schedule_teknisi_shift_1_end', '17:00'),
            'shift_1_cutoff' => Setting::getValue('schedule_teknisi_shift_1_cutoff', '13:00'),
            'shift_2_start' => Setting::getValue('schedule_teknisi_shift_2_start', '15:00'),
            'shift_2_end' => Setting::getValue('schedule_teknisi_shift_2_end', '00:00'),
            'shift_2_cutoff' => Setting::getValue('schedule_teknisi_shift_2_cutoff', '17:00'),
            'longshift_start' => Setting::getValue('schedule_teknisi_longshift_start', '08:00'),
            'longshift_end' => Setting::getValue('schedule_teknisi_longshift_end', '20:00'),
            'longshift_cutoff' => Setting::getValue('schedule_teknisi_longshift_cutoff', '13:00'),
        ];
    }

    public function isTodayLongshift(string $group): bool
    {
        $settingKey = $group === 'wash' ? 'weekly_schedule_wash' : 'weekly_schedule_teknisi';
        $scheduleRaw = (string)Setting::getValue($settingKey, '{}');
        $schedule = json_decode($scheduleRaw, true);
        if (!is_array($schedule)) {
            return false;
        }

        $todayKey = now()->englishDayOfWeek;
        $todaySchedule = $schedule[$todayKey] ?? null;
        if (!is_array($todaySchedule)) {
            return false;
        }

        $isEnabled = !empty($todaySchedule['enabled']);
        $shift = (string)($todaySchedule['shift'] ?? 'shift1');

        return $isEnabled && $shift === 'longshift';
    }

    public function resolveScheduleGroup(User $user): string
    {
        $roleName = strtolower((string)($user->role?->name ?? ''));
        if (in_array($roleName, ['kasir-wash', 'karyawan-wash'], true)) {
            return 'wash';
        }

        if (Schema::hasTable('wash_employees') && WashEmployee::query()->where('user_id', $user->id)->exists()) {
            return 'wash';
        }

        return 'teknisi';
    }

    public function checkClockInRules(User $user): ?array
    {
        $today = today();

        $alreadyClockedInToday = \App\Models\TechnicianAttendance::where('user_id', $user->id)
            ->where(function ($q) use ($today) {
                $q->whereDate('clock_in', $today->toDateString())
                    ->orWhereDate('work_date', $today->toDateString());
            })
            ->exists();

        if ($alreadyClockedInToday) {
            return ['error' => 'Gagal: Anda sudah melakukan absen masuk hari ini.'];
        }

        $hasLeaveRequest = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->exists();

        if ($hasLeaveRequest) {
            return ['error' => 'Gagal: Anda sedang dalam masa cuti/izin hari ini.'];
        }

        $clockInWindow = $this->resolveClockInWindow($user);
        $clockInStart = $clockInWindow['start'];
        $clockInEnd = $clockInWindow['end'];
        $currentTime = now()->format('H:i');

        if (!$this->isTimeWithinRange($currentTime, $clockInStart, $clockInEnd)) {
            return ['error' => "Clock In only allowed between {$clockInStart} - {$clockInEnd} WIB."];
        }

        $allowAfterCutoff = (bool)Setting::getValue('attendance_allow_after_cutoff', false);
        if (!$allowAfterCutoff && $this->isPastCutoffTime($clockInWindow['shift_cutoff'])) {
            return ['error' => 'Batas waktu absen masuk telah berakhir. Status kehadiran Anda akan dicatat sebagai Alpha.'];
        }

        return null;
    }

    public function checkClockOutRules(User $user, \App\Models\TechnicianAttendance $attendance): ?array
    {
        $clockOutStart = Setting::getValue('attendance_clock_out_start', '20:00');
        $clockOutEnd = Setting::getValue('attendance_clock_out_end', '01:00');
        $currentTime = now()->format('H:i');

        $isAllowed = $this->isTimeWithinRange($currentTime, $clockOutStart, $clockOutEnd);
        if (!$isAllowed) {
            return ['error' => "Clock Out only allowed between {$clockOutStart} - {$clockOutEnd} WIB."];
        }

        $cooldownMinutes = (int)Setting::getValue('attendance_cooldown_minutes', 30);
        $diffInMinutes = $attendance->clock_in?->diffInMinutes(now()) ?? 0;
        if ($diffInMinutes < $cooldownMinutes) {
            return ['error' => "Gagal: Jeda waktu absen masuk dan pulang minimal {$cooldownMinutes} menit. Baru {$diffInMinutes} menit berlalu."];
        }

        return null;
    }
}
