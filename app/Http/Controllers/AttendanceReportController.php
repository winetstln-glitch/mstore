<?php

namespace App\Http\Controllers;

use App\Services\Attendance\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceReportController extends Controller
{
    public function __construct(
        private readonly AttendanceReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : Carbon::now();
        $userId = $request->filled('user_id') ? (int) $request->user_id : null;
        $status = $request->filled('status') ? $request->status : null;
        $roleName = $request->filled('role') ? $request->role : null;

        $attendancesQuery = $this->reportService->getFilteredQuery(
            startDate: $startDate,
            endDate: $endDate,
            userId: $userId,
            status: $status,
            roleName: $roleName
        );

        $attendances = $attendancesQuery->latest('clock_in')->paginate(15)->withQueryString();
        $stats = $this->reportService->calculateDailyStats($attendancesQuery->get());

        $users = $this->getEligibleUsers();
        $roles = $this->reportService->getAllRoles();
        $statuses = $this->reportService->getAvailableStatuses();

        return view('technicians.attendance.index', compact(
            'attendances',
            'stats',
            'users',
            'roles',
            'statuses',
            'startDate',
            'endDate',
            'userId',
            'status',
            'roleName'
        ));
    }

    public function daily(Request $request)
    {
        $month = $request->query('month');
        if ($month) {
            $startDate = Carbon::parse($month)->startOfMonth();
            $endDate = Carbon::parse($month)->endOfMonth();
        } else {
            $startDate = $request->filled('start_date') 
                ? Carbon::parse($request->start_date) 
                : Carbon::today();
            $endDate = $request->filled('end_date') 
                ? Carbon::parse($request->end_date) 
                : Carbon::today();
        }

        $users = $this->getEligibleUsers();
        $attendancesQuery = $this->reportService->getFilteredQuery($startDate, $endDate);
        $allAttendances = $attendancesQuery->get();

        $attendancesByDate = $allAttendances
            ->groupBy(fn($a) => $a->work_date ? $a->work_date->toDateString() : $a->clock_in->toDateString())
            ->map(fn($items) => $items->keyBy('user_id'));

        $dates = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dates[] = $current->toDateString();
            $current->addDay();
        }

        return view('technicians.attendance.daily', compact(
            'users',
            'attendancesByDate',
            'dates',
            'startDate',
            'endDate',
            'month'
        ));
    }

    private function getEligibleUsers()
    {
        $query = \App\Models\User::whereHas('role', function ($q) {
            $q->whereNotIn('name', ['customer', 'koordinator', 'coordinator']);
        })->where('is_active', true)
          ->with('role');

        if (! $this->canViewAllAttendanceData()) {
            $query->where('id', Auth::id());
        }

        return $query->orderBy('name')->get();
    }

    private function canViewAllAttendanceData(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        
        return $user->hasAnyRole(['admin', 'finance', 'direktur', 'manager hrd', 'owner', 'owner pendiri', 'leader']);
    }
}
