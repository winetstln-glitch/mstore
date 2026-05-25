<?php

namespace App\Services\Attendance;

use App\Models\Role;
use App\Models\TechnicianAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    public function getFilteredQuery(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $userId = null,
        ?string $status = null,
        ?string $roleName = null
    ): Builder {
        $query = TechnicianAttendance::with('user', 'user.role');

        if ($startDate && $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('clock_in', [
                    $startDate->startOfDay()->toDateTimeString(),
                    $endDate->endOfDay()->toDateTimeString()
                ])->orWhereBetween('work_date', [
                    $startDate->toDateString(),
                    $endDate->toDateString()
                ]);
            });
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($roleName) {
            $query->whereHas('user.role', fn($q) => $q->where('name', $roleName));
        }

        return $query;
    }

    public function calculateDailyStats(Collection $attendances): array
    {
        $stats = [
            'total_records' => $attendances->count(),
            'present' => $attendances->whereIn('status', ['present', 'late'])->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'leave' => $attendances->whereIn('status', ['leave', 'permit', 'sick'])->count(),
            'alpha' => $attendances->where('status', 'alpha')->count(),
            'total_work_minutes' => 0,
            'total_late_minutes' => 0,
        ];

        foreach ($attendances as $attendance) {
            if ($attendance->clock_in && $attendance->clock_out) {
                $workDuration = $attendance->clock_in->diffInMinutes($attendance->clock_out);
                $stats['total_work_minutes'] += $workDuration;
            }

            if ($attendance->status === 'late' && $attendance->clock_in) {
                $stats['total_late_minutes'] += $this->calculateLateMinutes($attendance);
            }
        }

        $stats['total_work_hours'] = round($stats['total_work_minutes'] / 60, 2);
        $stats['total_late_hours'] = round($stats['total_late_minutes'] / 60, 2);

        return $stats;
    }

    public function calculateUserStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $attendances = $this->getFilteredQuery($startDate, $endDate, $user->id)->get();
        $dailyStats = $this->calculateDailyStats($attendances);

        return [
            'user' => $user,
            'attendances' => $attendances,
            'stats' => $dailyStats,
            'daily_attendance' => $this->generateDailyAttendanceMatrix($user, $startDate, $endDate),
        ];
    }

    public function calculateUserStatsForUsers(Collection $users, string $startDateStr, string $endDateStr, ?string $selectedRole = null, ?string $selectedStatus = null): array
    {
        $startDate = Carbon::parse($startDateStr);
        $endDate = Carbon::parse($endDateStr);

        $reportData = [];
        $totalStats = [
            'total_records' => 0,
            'present' => 0,
            'late' => 0,
            'leave' => 0,
            'alpha' => 0,
            'total_work_minutes' => 0,
            'total_late_minutes' => 0,
        ];

        foreach ($users as $user) {
            if ($selectedRole && $user->role?->name !== $selectedRole) {
                continue;
            }

            $userStats = $this->calculateUserStats($user, $startDate, $endDate);

            if ($selectedStatus) {
                $filteredAttendances = $userStats['attendances']->where('status', $selectedStatus);
                $userStats['stats'] = $this->calculateDailyStats($filteredAttendances);
            }

            $reportData[] = $userStats;

            $totalStats['total_records'] += $userStats['stats']['total_records'];
            $totalStats['present'] += $userStats['stats']['present'];
            $totalStats['late'] += $userStats['stats']['late'];
            $totalStats['leave'] += $userStats['stats']['leave'];
            $totalStats['alpha'] += $userStats['stats']['alpha'];
            $totalStats['total_work_minutes'] += $userStats['stats']['total_work_minutes'];
            $totalStats['total_late_minutes'] += $userStats['stats']['total_late_minutes'];
        }

        $totalStats['total_work_hours'] = round($totalStats['total_work_minutes'] / 60, 2);
        $totalStats['total_late_hours'] = round($totalStats['total_late_minutes'] / 60, 2);

        return [
            'users_report' => $reportData,
            'total_stats' => $totalStats,
        ];
    }

    public function calculateLateMinutes(TechnicianAttendance $attendance): int
    {
        if (!$attendance->clock_in || $attendance->status !== 'late') {
            return 0;
        }

        $clockInWindow = $this->attendanceService->resolveClockInWindow($attendance->user);
        $officialStart = $clockInWindow['official_start'] ?? '08:00';
        $lateTolerance = (int) \App\Models\Setting::getValue('attendance_late_tolerance', 0);

        $officialStartMinutes = $this->attendanceService->timeToMinutes($officialStart, 8 * 60);
        $lateThreshold = $officialStartMinutes + $lateTolerance;
        $clockInMinutes = ((int) $attendance->clock_in->format('H') * 60) + (int) $attendance->clock_in->format('i');

        return max(0, $clockInMinutes - $lateThreshold);
    }

    public function generateDailyAttendanceMatrix(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $attendances = $this->getFilteredQuery($startDate, $endDate, $user->id)->get();
        $attendancesByDate = $attendances
            ->groupBy(fn($a) => $a->work_date ? $a->work_date->toDateString() : $a->clock_in->toDateString())
            ->map(fn($items) => $items->first());

        $dates = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateStr = $current->toDateString();
            $dates[$dateStr] = $attendancesByDate->get($dateStr);
            $current->addDay();
        }

        return collect($dates);
    }

    public function generateDailyAttendanceMatrixForUsers(Collection $users, Carbon $startDate, Carbon $endDate, ?string $selectedRole = null, ?string $selectedStatus = null): array
    {
        $matrix = [];

        foreach ($users as $user) {
            if ($selectedRole && $user->role?->name !== $selectedRole) {
                continue;
            }

            $userMatrix = $this->generateDailyAttendanceMatrix($user, $startDate, $endDate);
            $matrix[$user->id] = [
                'user' => $user,
                'attendances' => $userMatrix,
            ];
        }

        return $matrix;
    }

    public function getAllRoles(): Collection
    {
        return Role::whereNotIn('name', ['customer', 'koordinator', 'coordinator'])->orderBy('name')->get();
    }

    public function getAvailableStatuses(): array
    {
        return ['present', 'late', 'leave', 'permit', 'sick', 'alpha'];
    }
}
