<?php

namespace App\Services\Schedule;

use App\Models\Role;
use App\Models\Setting;
use App\Models\TechnicianDailySchedule;
use App\Models\TechnicianSchedule;
use App\Models\User;
use App\Models\WashEmployee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ScheduleService
{
    public function __construct() {}

    public function getShiftConfig(): array
    {
        return [
            'teknisi' => [
                'shift_1_start' => Setting::getValue('schedule_teknisi_shift_1_start', '08:00'),
                'shift_1_end' => Setting::getValue('schedule_teknisi_shift_1_end', '17:00'),
                'shift_2_start' => Setting::getValue('schedule_teknisi_shift_2_start', '15:00'),
                'shift_2_end' => Setting::getValue('schedule_teknisi_shift_2_end', '00:00'),
                'longshift_start' => Setting::getValue('schedule_teknisi_longshift_start', '08:00'),
                'longshift_end' => Setting::getValue('schedule_teknisi_longshift_end', '20:00'),
            ],
            'wash' => [
                'shift_1_start' => Setting::getValue('schedule_wash_shift_1_start', '08:00'),
                'shift_1_end' => Setting::getValue('schedule_wash_shift_1_end', '17:00'),
                'shift_2_start' => Setting::getValue('schedule_wash_shift_2_start', '15:00'),
                'shift_2_end' => Setting::getValue('schedule_wash_shift_2_end', '00:00'),
                'longshift_start' => Setting::getValue('schedule_wash_longshift_start', '08:00'),
                'longshift_end' => Setting::getValue('schedule_wash_longshift_end', '20:00'),
            ],
        ];
    }

    public function getScheduleUsersQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = User::whereHas('role', function ($q) {
            $q->whereNotIn('name', ['customer', 'koordinator', 'coordinator']);
        })->where('is_active', true);

        return $query;
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

    public function buildWeeksData(int $year, int $month, ?Collection $periods = null): array
    {
        $weeksData = [];
        $firstDayOfMonth = Carbon::createFromDate($year, $month, 1);
        $firstDayOfWeek = $firstDayOfMonth->copy()->startOfWeek();

        for ($i = 0; $i < 6; $i++) {
            $weekStart = $firstDayOfWeek->copy()->addWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();
            $weekNumber = $weekStart->weekOfYear;
            $weekYear = $weekStart->year;

            $isCurrentMonth = $weekStart->month === $month || $weekEnd->month === $month;
            if (!$isCurrentMonth && $i > 0) {
                continue;
            }

            $period = $periods?->get($weekNumber);
            $weeksData[] = [
                'week_number' => $weekNumber,
                'year' => $weekYear,
                'start' => $weekStart->copy(),
                'end' => $weekEnd->copy(),
                'period' => $period,
            ];
        }

        return $weeksData;
    }

    public function applyScheduleDisplayNames(Collection $users): void
    {
        foreach ($users as $user) {
            $user->schedule_name = $user->name;
            $user->schedule_group = $this->resolveUserGroup($user);
            $user->schedule_department = $user->role?->name ?? '';
            $user->schedule_position = $user->role?->name ?? '';
        }
    }

    public function applyScheduleMeta(Collection $users): void
    {
    }

    public function deduplicateScheduleUsers(Collection $users): Collection
    {
        return $users->unique('id')->values();
    }

    public function getAllRoles(): Collection
    {
        return Role::whereNotIn('name', ['customer', 'koordinator', 'coordinator'])->orderBy('name')->get();
    }
}
