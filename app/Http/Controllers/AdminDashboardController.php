<?php

namespace App\Http\Controllers;

use App\Models\TechnicianAttendance;
use App\Models\User;
use App\Services\Attendance\AttendanceReportService;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AttendanceReportService $attendanceReportService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function index()
    {
        Gate::authorize('dashboard.view');

        $today = Carbon::today();

        $totalEmployees = User::whereHas('role', function ($q) {
            $q->whereNotIn('name', ['customer']);
        })->where('is_active', true)->count();

        $todayAttendances = TechnicianAttendance::whereDate('clock_in', $today->toDateString())
            ->orWhereDate('work_date', $today->toDateString())
            ->get();

        $stats = [
            'total_employees' => $totalEmployees,
            'present' => $todayAttendances->whereIn('status', ['present', 'late'])->count(),
            'late' => $todayAttendances->where('status', 'late')->count(),
            'sick_permit' => $todayAttendances->whereIn('status', ['sick', 'leave', 'permit'])->count(),
            'alpha' => $todayAttendances->where('status', 'alpha')->count(),
            'clocked_out' => $todayAttendances->whereNotNull('clock_out')->count(),
        ];

        $recentLogs = $this->auditLogService->getRecentLogs(15);

        return view('admin.dashboard', compact('stats', 'recentLogs'));
    }

    public function auditTrail(Request $request)
    {
        Gate::authorize('audit.view');

        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;
        $action = $request->input('action');
        $modelType = $request->input('model_type');

        $logs = $this->auditLogService->getAuditLogsQuery($userId, $action, $modelType)
            ->paginate(20);

        $users = User::whereHas('role', function ($q) {
            $q->whereNotIn('name', ['customer']);
        })->orderBy('name')->get();

        return view('admin.audit-trail', compact('logs', 'users'));
    }
}
