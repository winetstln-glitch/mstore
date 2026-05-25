<?php

namespace App\Http\Controllers;

use App\Actions\Attendance\ClockInAction;
use App\Actions\Attendance\ClockOutAction;
use App\Http\Requests\Attendance\ClockInRequest;
use App\Http\Requests\Attendance\ClockOutRequest;
use App\Models\Role;
use App\Models\TechnicianAttendance;
use App\Services\Attendance\AttendanceReportService;
use App\Services\Attendance\AttendanceService;
use App\Services\Schedule\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AttendanceRefactoredController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly AttendanceReportService $attendanceReportService,
        private readonly ScheduleService $scheduleService,
        private readonly ClockInAction $clockInAction,
        private readonly ClockOutAction $clockOutAction
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('attendance.view');

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $day = (int) $request->input('day', now()->day);

        $usersQuery = $this->scheduleService->getScheduleUsersQuery();
        if (! $this->attendanceService->canViewAllAttendanceData(Auth::user())) {
            $usersQuery->where('id', Auth::id());
        }

        $users = $usersQuery->orderBy('name')->get();
        $roles = $this->attendanceReportService->getAllRoles();
        $statuses = $this->attendanceReportService->getAvailableStatuses();

        $selectedUser = $request->input('user') ? $users->find((int) $request->input('user')) : null;
        $selectedRole = $request->input('role');
        $selectedStatus = $request->input('status');

        $firstDay = Carbon::createFromDate($year, $month, 1);
        $lastDay = $firstDay->copy()->endOfMonth();

        $targetUsers = $selectedUser ? collect([$selectedUser]) : $users;
        $reportData = $this->attendanceReportService->calculateUserStatsForUsers(
            $targetUsers,
            $firstDay->toDateString(),
            $lastDay->toDateString(),
            $selectedRole,
            $selectedStatus
        );

        if ($request->routeIs('attendance.index')) {
            $firstDay = Carbon::create($year, $month, $day);
            $lastDay = $firstDay->copy();
        }

        $attendanceMatrix = $this->attendanceReportService->generateDailyAttendanceMatrixForUsers(
            $targetUsers,
            $firstDay,
            $lastDay,
            $selectedRole,
            $selectedStatus
        );

        return view('technicians.attendance.index', array_merge([
            'users' => $users,
            'roles' => $roles,
            'statuses' => $statuses,
            'selectedUser' => $selectedUser,
            'selectedRole' => $selectedRole,
            'selectedStatus' => $selectedStatus,
            'year' => $year,
            'month' => $month,
            'day' => $day,
        ], $reportData, ['attendance_matrix' => $attendanceMatrix]));
    }

    public function daily(Request $request)
    {
        Gate::authorize('attendance.view');

        $date = $request->input('date', now()->toDateString());
        $date = Carbon::parse($date);

        $usersQuery = $this->scheduleService->getScheduleUsersQuery();
        if (! $this->attendanceService->canViewAllAttendanceData(Auth::user())) {
            $usersQuery->where('id', Auth::id());
        }

        $users = $usersQuery->orderBy('name')->get();
        $roles = $this->attendanceReportService->getAllRoles();
        $statuses = $this->attendanceReportService->getAvailableStatuses();

        $selectedUser = $request->input('user') ? $users->find((int) $request->input('user')) : null;
        $selectedRole = $request->input('role');
        $selectedStatus = $request->input('status');

        $targetUsers = $selectedUser ? collect([$selectedUser]) : $users;
        $reportData = $this->attendanceReportService->calculateUserStatsForUsers(
            $targetUsers,
            $date->toDateString(),
            $date->toDateString(),
            $selectedRole,
            $selectedStatus
        );

        $attendanceMatrix = $this->attendanceReportService->generateDailyAttendanceMatrixForUsers(
            $targetUsers,
            $date,
            $date,
            $selectedRole,
            $selectedStatus
        );

        return view('technicians.attendance.daily', array_merge([
            'users' => $users,
            'roles' => $roles,
            'statuses' => $statuses,
            'selectedUser' => $selectedUser,
            'selectedRole' => $selectedRole,
            'selectedStatus' => $selectedStatus,
            'date' => $date,
        ], $reportData, ['attendance_matrix' => $attendanceMatrix]));
    }

    public function create()
    {
        Gate::authorize('attendance.create');

        $today = Carbon::today();
        $user = Auth::user();
        $attendance = $this->attendanceService->getTodayAttendance($user);
        $clockInWindow = $this->attendanceService->resolveClockInWindow($user);

        $hasApprovedLeave = $this->attendanceService->hasApprovedLeave($user, $today);

        return view('technicians.attendance.create', compact(
            'attendance',
            'clockInWindow',
            'hasApprovedLeave'
        ));
    }

    public function store(ClockInRequest $request)
    {
        Gate::authorize('attendance.create');

        try {
            $data = $request->validated();
            $data['request'] = $request;

            $attendance = $this->clockInAction->execute(Auth::user(), $data);

            $redirect = $this->attendanceService->attendanceRedirectRoute($request);

            return redirect()->route($redirect)->with('success', 'Absensi masuk berhasil.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(TechnicianAttendance $attendance)
    {
        Gate::authorize('attendance.edit');

        if ($attendance->clock_out) {
            return redirect()->route('attendance.index')->with('error', 'Anda sudah melakukan absensi pulang.');
        }

        $clockInWindow = $this->attendanceService->resolveClockInWindow($attendance->user);

        return view('technicians.attendance.edit', compact('attendance', 'clockInWindow'));
    }

    public function update(ClockOutRequest $request, TechnicianAttendance $attendance)
    {
        Gate::authorize('attendance.edit');

        try {
            $data = $request->validated();
            $data['request'] = $request;

            $attendance = $this->clockOutAction->execute($attendance->user, $attendance, $data);

            $redirect = $this->attendanceService->attendanceRedirectRoute($request);

            return redirect()->route($redirect)->with('success', 'Absensi pulang berhasil.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
