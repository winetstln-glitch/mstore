<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\StoreManualAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Requests\UpdateManualAttendanceRequest;
use App\Jobs\Attendance\SendClockInNotificationJob;
use App\Jobs\Attendance\SendClockOutNotificationJob;
use App\Jobs\Attendance\SendKioskClockInNotificationJob;
use App\Jobs\Attendance\SendKioskClockOutNotificationJob;
use App\Jobs\Attendance\SendManualAttendanceNotificationJob;
use App\Models\LeaveRequest;
use App\Models\SalaryAdjustment;
use App\Models\Setting;
use App\Models\TechnicianAttendance;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AttendanceExportService;
use App\Services\AttendanceNotificationService;
use App\Services\AttendancePayrollService;
use App\Services\AttendanceService;
use App\Traits\HasAttendanceFilters;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TechnicianAttendanceController extends Controller implements HasMiddleware
{
    use HasAttendanceFilters;

    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly AttendancePayrollService $payrollService,
        private readonly AttendanceNotificationService $notificationService,
        private readonly AttendanceExportService $exportService,
    ) {
    }

    protected function canViewAllAttendanceData(): bool
    {
        return $this->attendanceService->canViewAllAttendanceData(Auth::user());
    }

    protected function isAdminOrHrdManager(): bool
    {
        return $this->attendanceService->isAdminOrHrdManager(Auth::user());
    }

    protected function isUserCoordinator(?\App\Models\User $user): bool
    {
        return $this->attendanceService->isUserCoordinator($user);
    }

    protected function isAttendanceEligibleUser(?\App\Models\User $user): bool
    {
        return $this->attendanceService->isAttendanceEligibleUser($user);
    }

    protected function isUserOffOnDate(\App\Models\User $user, string $dateStr): bool
    {
        return $this->attendanceService->isUserOffOnDate($user, $dateStr);
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:attendance.view', only: ['index', 'daily', 'exportExcel', 'payslip']),
            new Middleware('permission:attendance.create', only: ['kiosk', 'kioskScan']),
            new Middleware('permission:attendance.edit', only: ['edit']),
            new Middleware('permission:attendance.delete', only: ['destroy', 'bulkDestroy']),
        ];
    }

    public function daily(Request $request)
    {
        $user = Auth::user();
        if ($this->attendanceService->isUserCoordinator($user)) {
            abort(403, 'Anda tidak diizinkan mengakses halaman ini.');
        }

        $month = $request->query('month');
        if ($month) {
            $startDate = Carbon::parse($month)->startOfMonth()->toDateString();
            $endDate = Carbon::parse($month)->endOfMonth()->toDateString();
        } else {
            $startDate = $request->query('start_date', date('Y-m-d'));
            $endDate = $request->query('end_date', date('Y-m-d'));
        }
        $status = (string) $request->query('status', '');
        $search = (string) $request->query('search', '');

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $usersQuery = \App\Models\User::whereHas('role', function ($q) {
            $q->whereNotIn('name', [\App\Models\Role::CUSTOMER, \App\Models\Role::COORDINATOR]);
        })->where('is_active', true)
            ->with('role')
            ->orderBy('name');

        if ($search) {
            $usersQuery->where('name', 'LIKE', "%{$search}%")
                ->orWhere('username', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
        }

        $users = $usersQuery->get();

        $attendancesQuery = TechnicianAttendance::whereBetween('clock_in', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orWhereBetween('work_date', [$startDate, $endDate])
            ->with('user');

        $allAttendances = $attendancesQuery->get();

        $attendancesByDate = $allAttendances
            ->groupBy(fn($a) => $a->work_date?->toDateString() ?? $a->clock_in?->toDateString())
            ->map(fn($items) => $items->keyBy('user_id'));

        $dates = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        while ($current->lte($end)) {
            $dates[] = $current->toDateString();
            $current->addDay();
        }

        // Pre-calculate isOff for all users and dates for the view
        $isOffByUserAndDate = [];
        foreach ($users as $usr) {
            foreach ($dates as $date) {
                $isOffByUserAndDate[$usr->id][$date] = $this->attendanceService->isUserOffOnDate($usr, $date);
            }
        }

        return view('technicians.attendance.daily', compact(
            'users', 'attendancesByDate', 'dates', 'startDate', 'endDate', 'status', 'search', 'isOffByUserAndDate'
        ));
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if ($this->attendanceService->isUserCoordinator($user)) {
            abort(403, 'Anda tidak diizinkan mengakses halaman ini.');
        }

        $query = $this->getFilteredAttendanceQuery($request);
        $statsQuery = clone $query;
        $allAttendances = $statsQuery->get();

        $stats = [
            'present' => $allAttendances->whereIn('status', ['present', 'late'])->count(),
            'late' => $allAttendances->where('status', 'late')->count(),
            'leave' => $allAttendances->where('status', 'leave')->count(),
            'permit' => $allAttendances->where('status', 'permit')->count(),
            'sick' => $allAttendances->where('status', 'sick')->count(),
            'alpha' => $allAttendances->where('status', 'alpha')->count(),
            'total_days' => $allAttendances->count(),
        ];

        $attendances = $query->latest('clock_in')->paginate(15)->withQueryString();

        $techniciansQuery = \App\Models\User::whereHas('role', function ($q) {
            $q->whereNotIn('name', [\App\Models\Role::CUSTOMER, \App\Models\Role::COORDINATOR]);
        })->where('is_active', true)
            ->with('role');

        if (!$this->attendanceService->canViewAllAttendanceData($user)) {
            $techniciansQuery->where('id', Auth::id());
        }

        $technicians = $techniciansQuery->orderBy('name')->get();
        $status = (string) $request->query('status', '');
        $search = (string) $request->query('search', '');
        $user_id = (string) $request->query('user_id', '');
        $date = (string) $request->query('date', '');
        $start_date = (string) $request->query('start_date', '');
        $end_date = (string) $request->query('end_date', '');
        $month = (string) $request->query('month', '');

        return view('technicians.attendance.index', compact('attendances', 'technicians', 'stats', 'status', 'search', 'user_id', 'date', 'start_date', 'end_date', 'month'));
    }

    public function payslip(Request $request)
    {
        $user = Auth::user();
        if ($this->attendanceService->isUserCoordinator($user)) {
            abort(403, 'Anda tidak diizinkan mengakses halaman ini.');
        }

        $techniciansQuery = \App\Models\User::whereHas('role', function ($q) {
            $q->whereNotIn('name', [\App\Models\Role::CUSTOMER, \App\Models\Role::COORDINATOR]);
        })->where('is_active', true)
            ->with('role');

        if (!$this->attendanceService->canViewAllAttendanceData($user)) {
            $techniciansQuery->where('id', Auth::id());
        }

        $technicians = $techniciansQuery->orderBy('name')->get();

        $attendances = $this->getFilteredAttendanceQuery($request)->oldest('clock_in')->get();
        $allAdjustments = $this->getFilteredAdjustmentsQuery($request)->get()->groupBy('user_id');

        $summary = $this->payrollService->calculateAttendanceSummary($attendances, $allAdjustments, $request);
        $status = (string) $request->query('status', '');
        $search = (string) $request->query('search', '');
        $user_id = (string) $request->query('user_id', '');
        $date = (string) $request->query('date', '');
        $start_date = (string) $request->query('start_date', '');
        $end_date = (string) $request->query('end_date', '');
        $month = (string) $request->query('month', '');

        return view('technicians.attendance.payslip', compact('summary', 'request', 'technicians', 'status', 'search', 'user_id', 'date', 'start_date', 'end_date', 'month'));
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        if ($this->attendanceService->isUserCoordinator($user)) {
            abort(403, 'Anda tidak diizinkan mengakses halaman ini.');
        }

        if ($request->query('scope') === 'daily') {
            $month = $request->query('month');
            if ($month) {
                $startDate = Carbon::parse($month)->startOfMonth();
                $endDate = Carbon::parse($month)->endOfMonth();
            } else {
                $startDate = $request->query('start_date', date('Y-m-d'));
                $endDate = $request->query('end_date', date('Y-m-d'));
            }

            if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }

            $users = \App\Models\User::whereHas('role', function ($q) {
                $q->whereNotIn('name', [\App\Models\Role::CUSTOMER, \App\Models\Role::COORDINATOR]);
            })->where('is_active', true)
                ->with('role')
                ->orderBy('name')
                ->get();

            $attendancesQuery = TechnicianAttendance::whereBetween('clock_in', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->orWhereBetween('work_date', [$startDate, $endDate])
                ->with('user');

            $allAttendances = $attendancesQuery->get();
            $attendancesByDate = $allAttendances
                ->groupBy(fn($a) => $a->work_date?->toDateString() ?? $a->clock_in?->toDateString())
                ->map(fn($items) => $items->keyBy('user_id'));

            $dates = [];
            $current = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            while ($current->lte($end)) {
                $dates[] = $current->toDateString();
                $current->addDay();
            }

            return response()->streamDownload(
                $this->exportService->exportDailyToXlsx($users, $attendancesByDate, $dates, $startDate, $endDate),
                'rincian_kehadiran_harian_' . now()->format('Y-m-d_His') . '.xlsx'
            );
        }

        $attendances = $this->getFilteredAttendanceQuery($request)->oldest('clock_in')->get();

        if ($request->query('download') === 'details') {
            return response()->streamDownload(
                $this->exportService->exportDetailsToXlsx($attendances),
                'rincian_kehadiran_' . now()->format('Y-m-d_His') . '.xlsx'
            );
        }

        $allAdjustments = $this->getFilteredAdjustmentsQuery($request)->get()->groupBy('user_id');
        $summary = $this->payrollService->calculateAttendanceSummary($attendances, $allAdjustments, $request);

        return response()->streamDownload(
            $this->exportService->exportSummaryToXlsx($summary),
            'rekap_teknisi_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    public function recapToFinance(Request $request)
    {
        $user = Auth::user();
        if (!$this->attendanceService->isAdminOrHrdManager($user)) {
            abort(403, 'Unauthorized');
        }

        $attendances = $this->getFilteredAttendanceQuery($request)->oldest('clock_in')->get();
        $pendingAdjustments = $this->getFilteredAdjustmentsQuery($request, 'pending')->get();
        $allAdjustments = $pendingAdjustments->groupBy('user_id');

        $summary = $this->payrollService->calculateAttendanceSummary($attendances, $allAdjustments, $request);

        if ($summary->isEmpty() && $pendingAdjustments->isEmpty()) {
            return back()->with('error', __('No attendance records or pending adjustments found for the selected period.'));
        }

        $totalAmount = $summary->sum('total_salary');

        if ($totalAmount <= 0) {
            return back()->with('error', __('Total salary amount is zero or negative. No transaction created.'));
        }

        $period = '';
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $period = Carbon::parse($request->start_date)->translatedFormat('d F Y') . ' - ' . Carbon::parse($request->end_date)->translatedFormat('d F Y');
        } elseif ($request->filled('month')) {
            $period = Carbon::parse($request->month)->translatedFormat('F Y');
        } elseif ($request->filled('date')) {
            $period = Carbon::parse($request->date)->translatedFormat('d F Y');
        } else {
            $period = __('All Time');
        }

        $description = "Pembayaran Gaji Teknisi Periode {$period}";
        if ($request->filled('user_id')) {
            $targetUser = \App\Models\User::find($request->user_id);
            if ($targetUser) {
                $description .= ' - ' . $targetUser->name;
            }
        }

        \App\Models\Transaction::create([
            'user_id' => Auth::id(),
            'type' => 'expense',
            'category' => 'Salary',
            'amount' => $totalAmount,
            'description' => $description,
            'transaction_date' => now(),
        ]);

        if ($pendingAdjustments->isNotEmpty()) {
            \App\Models\SalaryAdjustment::whereIn('id', $pendingAdjustments->pluck('id'))->update(['status' => 'processed']);
        }

        return back()->with('success', __('Salary expense of :amount has been recorded in Finance.', ['amount' => number_format($totalAmount, 0, ',', '.')]));
    }

    public function sendNotification(TechnicianAttendance $attendance)
    {
        $user = Auth::user();
        if (!$this->attendanceService->isAdminOrHrdManager($user)) {
            abort(403, 'Unauthorized');
        }

        $targetUser = $attendance->user;
        if (!$targetUser) {
            return back()->with('error', __('User not found.'));
        }

        $result = $this->notificationService->sendSingleAttendanceNotification($user, $targetUser, $attendance);

        if (!$result['sent']) {
            return back()->with('error', __('User does not have a phone number or Telegram Chat ID.'));
        }

        $channelList = implode(' & ', $result['channels']);
        return back()->with('success', __('Notification sent via :channels.', ['channels' => $channelList]));
    }

    public function storeManual(StoreManualAttendanceRequest $request)
    {
        $user = Auth::user();
        if (!$this->attendanceService->isAdminOrHrdManager($user)) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validated();

        $exists = TechnicianAttendance::where('user_id', $validated['user_id'])
            ->where(function ($q) use ($validated) {
                $q->whereDate('clock_in', $validated['date'])
                    ->orWhereDate('work_date', $validated['date']);
            })
            ->exists();

        if ($exists) {
            return back()->with('error', __('Attendance record for this user on this date already exists.'));
        }

        $data = [
            'user_id' => $validated['user_id'],
            'work_date' => $validated['date'],
            'status' => $validated['status'],
            'notes' => $validated['notes'],
        ];

        if (!empty($validated['clock_in_create'])) {
            $data['clock_in'] = $validated['date'] . ' ' . $validated['clock_in_create'] . ':00';
        } else {
            $data['clock_in'] = $validated['date'] . ' 08:00:00';
        }

        if (!empty($validated['clock_out_create'])) {
            $data['clock_out'] = $validated['date'] . ' ' . $validated['clock_out_create'] . ':00';
        }

        $attendance = TechnicianAttendance::create($data);

        \App\Models\AuditLog::log(
            'create',
            $attendance,
            [],
            $attendance->toArray(),
            'Menambah absensi manual untuk ' . ($attendance->user?->name ?? 'Unknown User')
        );

        $targetUser = $attendance->user;
        if ($targetUser) {
            SendManualAttendanceNotificationJob::dispatch($targetUser, $attendance, $user)->afterResponse();
        }

        return back()->with('success', __('Manual attendance added successfully.'));
    }

    public function updateManual(UpdateManualAttendanceRequest $request, $id)
    {
        $user = Auth::user();
        if (!$this->attendanceService->isAdminOrHrdManager($user)) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validated();
        $attendance = TechnicianAttendance::findOrFail($id);
        $oldValues = $attendance->toArray();

        $updateData = [
            'work_date' => $validated['date'],
            'status' => $validated['status'],
            'notes' => $validated['notes'],
        ];

        if (!empty($validated['clock_in'])) {
            $updateData['clock_in'] = $validated['date'] . ' ' . $validated['clock_in'] . ':00';
        }

        if (!empty($validated['clock_out'])) {
            $updateData['clock_out'] = $validated['date'] . ' ' . $validated['clock_out'] . ':00';
        }

        $attendance->update($updateData);

        \App\Models\AuditLog::log(
            'update',
            $attendance,
            $oldValues,
            $attendance->toArray(),
            'Mengedit absensi untuk ' . ($attendance->user?->name ?? 'Unknown User')
        );

        return back()->with('success', __('Attendance updated successfully.'));
    }

    public function create()
    {
        if (!$this->attendanceService->isAttendanceEligibleUser(Auth::user())) {
            return redirect()->route('dashboard')->withErrors([
                'message' => __('Role Anda tidak diizinkan untuk absensi mandiri.'),
            ]);
        }

        $todayAttendance = TechnicianAttendance::where('user_id', Auth::id())
            ->where(function ($q) {
                $q->whereDate('clock_in', today()->toDateString())
                    ->orWhereDate('work_date', today()->toDateString());
            })
            ->first();

        $monthAttendances = TechnicianAttendance::where('user_id', Auth::id())
            ->whereMonth('clock_in', now()->month)
            ->whereYear('clock_in', now()->year)
            ->latest('clock_in')
            ->get();

        $attendanceSummary = [
            'masuk' => $monthAttendances->whereIn('status', ['present', 'late'])->count(),
            'izin' => $monthAttendances->whereIn('status', ['permit', 'leave'])->count(),
            'sakit' => $monthAttendances->where('status', 'sick')->count(),
        ];

        $clockInWindow = $this->attendanceService->resolveClockInWindow(Auth::user());
        $clockInStart = $clockInWindow['start'];
        $clockInEnd = $clockInWindow['end'];
        $clockOutStart = Setting::getValue('attendance_clock_out_start', '20:00');
        $clockOutEnd = Setting::getValue('attendance_clock_out_end', '01:00');
        $leaveQuota = Setting::getValue('technician_leave_quota', 3);
        $faceVerificationEnabled = (string)Setting::getValue('attendance_face_verification_enabled', '0');
        $shiftInfo = $this->attendanceService->resolveTodayShiftInfo(Auth::user());
        $attendanceOfficeLat = (float)Setting::getValue('attendance_office_lat', Setting::getValue('office_latitude', 0));
        $attendanceOfficeLng = (float)Setting::getValue('attendance_office_lng', Setting::getValue('office_longitude', 0));
        $attendanceRadius = (float)Setting::getValue('attendance_radius', Setting::getValue('attendance_max_distance_meters', 100));
        $attendancePhotoRequired = $this->attendanceService->isAttendancePhotoRequired();

        return view('technicians.attendance.create', compact(
            'todayAttendance',
            'clockInStart',
            'clockInEnd',
            'clockOutStart',
            'clockOutEnd',
            'faceVerificationEnabled',
            'attendanceSummary',
            'leaveQuota',
            'shiftInfo',
            'monthAttendances',
            'attendanceOfficeLat',
            'attendanceOfficeLng',
            'attendanceRadius',
            'attendancePhotoRequired'
        ));
    }

    public function kiosk()
    {
        $todayLogs = TechnicianAttendance::with('user')
            ->where(function ($q) {
                $q->whereDate('clock_in', today()->toDateString())
                    ->orWhereDate('work_date', today()->toDateString());
            })
            ->latest('clock_in')
            ->limit(50)
            ->get();

        return view('technicians.attendance.kiosk', compact('todayLogs'));
    }

    public function kioskScan(Request $request)
    {
        $payload = $request->validate([
            'card_code' => ['required', 'string', 'max:255'],
        ]);

        $cardCode = trim((string)$payload['card_code']);
        $user = $this->attendanceService->resolveAttendanceUser($cardCode);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('ID Card tidak dikenali atau pengguna tidak aktif.'),
            ], 422);
        }

        $todayAttendance = TechnicianAttendance::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereDate('clock_in', today()->toDateString())
                    ->orWhereDate('work_date', today()->toDateString());
            })
            ->first();

        if ($todayAttendance && $todayAttendance->status === 'alpha') {
            return response()->json([
                'success' => false,
                'message' => __('Anda sudah tercatat sebagai Alpha hari ini. Tidak bisa melakukan clock-in.'),
            ], 422);
        }

        if (!$todayAttendance) {
            $hasLeaveRequest = LeaveRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->exists();

            if ($hasLeaveRequest) {
                return response()->json([
                    'success' => false,
                    'message' => __('Gagal: :name sedang dalam masa cuti/izin hari ini.', ['name' => $user->name]),
                ], 422);
            }

            $clockInWindow = $this->attendanceService->resolveClockInWindow($user);
            $clockInStart = $clockInWindow['start'];
            $clockInEnd = $clockInWindow['end'];
            $shiftCutoff = $clockInWindow['shift_cutoff'];
            $currentTime = now()->format('H:i');
            if (!$this->attendanceService->isTimeWithinRange($currentTime, $clockInStart, $clockInEnd)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Clock In only allowed between :start - :end WIB.', ['start' => $clockInStart, 'end' => $clockInEnd]),
                ], 422);
            }

            $allowAfterCutoff = (bool)Setting::getValue('attendance_allow_after_cutoff', false);
            if (!$allowAfterCutoff && $this->attendanceService->isPastCutoffTime($shiftCutoff)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Batas waktu absen masuk telah berakhir. Status kehadiran Anda akan dicatat sebagai Alpha.'),
                ], 422);
            }

            $status = $this->attendanceService->determineClockInStatus(
                (string)($clockInWindow['official_start'] ?? $clockInStart),
                (string)($clockInWindow['shift_cutoff'] ?? $clockInEnd)
            );

            $attendance = TechnicianAttendance::create([
                'user_id' => $user->id,
                'work_date' => today()->toDateString(),
                'clock_in' => now(),
                'status' => $status,
                'notes' => 'Kiosk scan ID Card otomatis. Admin: ' . (Auth::user()?->name ?? 'Unknown'),
            ]);

            SendKioskClockInNotificationJob::dispatch($user, $attendance)->afterResponse();

            return response()->json([
                'success' => true,
                'action' => 'clock_in',
                'message' => __('Absen masuk berhasil: :name (:status)', [
                    'name' => $user->name,
                    'status' => strtoupper(__($attendance->status)),
                ]),
                'data' => [
                    'name' => $user->name,
                    'status' => $attendance->status,
                    'time' => $attendance->clock_in?->format('H:i:s') ?? '-',
                ],
            ]);
        }

        if ($todayAttendance->clock_out) {
            return response()->json([
                'success' => false,
                'message' => __(':name sudah absen lengkap (Masuk & Pulang) hari ini.', ['name' => $user->name]),
            ], 422);
        }

        $cooldownMinutes = (int)Setting::getValue('attendance_kiosk_cooldown_minutes', 5);
        $diffInMinutes = $todayAttendance->clock_in?->diffInMinutes(now()) ?? 0;
        if ($diffInMinutes < $cooldownMinutes) {
            return response()->json([
                'success' => false,
                'message' => __('Tunggu :rem menit lagi untuk absen pulang (Cooldown Kiosk).', [
                    'rem' => $cooldownMinutes - $diffInMinutes,
                ]),
            ], 422);
        }

        $todayAttendance->update([
            'clock_out' => now(),
            'notes' => trim(($todayAttendance->notes ?? '') . "\nClock Out Kiosk otomatis oleh " . (Auth::user()?->name ?? 'Unknown')),
        ]);

        SendKioskClockOutNotificationJob::dispatch($user, $todayAttendance)->afterResponse();

        return response()->json([
            'success' => true,
            'action' => 'clock_out',
            'message' => __('Absen pulang berhasil: :name', ['name' => $user->name]),
            'data' => [
                'name' => $user->name,
                'status' => $todayAttendance->status,
                'time' => $todayAttendance->clock_in?->format('H:i:s') ?? '-',
                'clock_out' => $todayAttendance->clock_out?->format('H:i:s') ?? '-',
            ],
        ]);
    }

    public function store(StoreAttendanceRequest $request)
    {
        if (!$this->attendanceService->isAttendanceEligibleUser(Auth::user())) {
            return redirect()->route($this->attendanceService->attendanceRedirectRoute($request))->withErrors([
                'message' => __('Role Anda tidak diizinkan untuk absensi.'),
            ]);
        }

        $lockKey = 'attendance-clock-in-' . Auth::id();
        $lock = Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            return back()->withErrors(['message' => __('Permintaan absensi sedang diproses. Mohon tunggu sebentar.')]);
        }

        try {
            $currentUser = Auth::user();
            $rulesCheck = $this->attendanceService->checkClockInRules($currentUser);
            if ($rulesCheck) {
                return back()->withErrors(['message' => $rulesCheck['error']]);
            }

            $photoMaxKb = $this->attendanceService->resolveAttendancePhotoMaxKb();
            $photoRequired = $this->attendanceService->isAttendancePhotoRequired();
            $photoRule = $photoRequired ? 'required' : 'nullable';

            $request->validate([
                'photo' => $photoRule . '|image|max:' . $photoMaxKb,
                'latitude' => 'nullable',
                'longitude' => 'nullable',
            ], [
                'photo.max' => __('Ukuran foto terlalu besar. Maksimal :max KB.', ['max' => $photoMaxKb]),
                'photo.required' => __('Foto selfie wajib diunggah untuk absensi.'),
            ]);

            if (!$request->latitude || !$request->longitude) {
                if (!$request->hasFile('photo')) {
                    return back()->withErrors(['message' => __('GPS tidak terdeteksi. Silakan ambil foto sebagai bukti kehadiran.')]);
                }
            }

            $deviceFingerprint = $this->attendanceService->resolveAttendanceDeviceFingerprint($request, $currentUser);
            if (!$currentUser?->attendance_device_hash) {
                $currentUser?->forceFill([
                    'attendance_device_hash' => $deviceFingerprint,
                    'attendance_device_locked_at' => now(),
                ])->save();
            } elseif ((string)$currentUser?->attendance_device_hash !== $deviceFingerprint) {
                $currentUser?->forceFill([
                    'attendance_device_hash' => $deviceFingerprint,
                    'attendance_device_locked_at' => now(),
                ])->save();
            }

            $officeLat = Setting::getValue('attendance_office_lat');
            $officeLng = Setting::getValue('attendance_office_lng');
            $radius = Setting::getValue('attendance_radius', 100);

            if ($officeLat && $officeLng && $request->latitude && $request->longitude) {
                $distance = $this->attendanceService->calculateDistance($request->latitude, $request->longitude, $officeLat, $officeLng);
                if ($distance > $radius) {
                    return back()->withErrors(['message' => __('You are too far from the office. Distance: :dist m. Max: :max m.', ['dist' => round($distance), 'max' => $radius])]);
                }
            }

            $path = $request->hasFile('photo')
                ? $request->file('photo')->store('attendance-photos', 'public')
                : null;

            $clockInWindow = $this->attendanceService->resolveClockInWindow($currentUser);
            $attendance = TechnicianAttendance::create([
                'user_id' => Auth::id(),
                'work_date' => today()->toDateString(),
                'clock_in' => now(),
                'photo_clock_in' => $path,
                'lat_clock_in' => $request->latitude ?: null,
                'lng_clock_in' => $request->longitude ?: null,
                'device_fingerprint_clock_in' => $deviceFingerprint,
                'ip_clock_in' => (string)($request->ip() ?? ''),
                'user_agent_clock_in' => mb_substr((string)$request->userAgent(), 0, 255),
                'status' => $this->attendanceService->determineClockInStatus(
                    (string)($clockInWindow['official_start'] ?? $clockInWindow['start']),
                    (string)($clockInWindow['shift_cutoff'] ?? $clockInWindow['end'])
                ),
                'notes' => $request->notes,
            ]);

            \App\Models\AuditLog::log(
                'clock_in',
                $attendance,
                [],
                $attendance->toArray(),
                'Check-in absensi untuk ' . (Auth::user()?->name ?? 'Unknown')
            );

            if ($currentUser) {
                SendClockInNotificationJob::dispatch($currentUser, $attendance)->afterResponse();
            }

            return redirect()->route($this->attendanceService->attendanceRedirectRoute($request))->with('success', __('Clock In successful!'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Attendance Store Fatal Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            return back()->withErrors(['message' => __('Terjadi kesalahan saat memproses absensi: ') . $e->getMessage()]);
        } finally {
            optional($lock)->release();
        }
    }

    public function update(UpdateAttendanceRequest $request, $id)
    {
        if (!$this->attendanceService->isAttendanceEligibleUser(Auth::user())) {
            return redirect()->route($this->attendanceService->attendanceRedirectRoute($request))->withErrors([
                'message' => __('Role Anda tidak diizinkan untuk absensi.'),
            ]);
        }

        try {
            $attendance = TechnicianAttendance::where('user_id', Auth::id())->findOrFail($id);
            $currentUser = Auth::user();

            $rulesCheck = $this->attendanceService->checkClockOutRules($currentUser, $attendance);
            if ($rulesCheck) {
                return back()->withErrors(['message' => $rulesCheck['error']]);
            }

            $photoMaxKb = $this->attendanceService->resolveAttendancePhotoMaxKb();
            $photoRequired = $this->attendanceService->isAttendancePhotoRequired();
            $photoRule = $photoRequired ? 'required' : 'nullable';

            $request->validate([
                'photo' => $photoRule . '|image|max:' . $photoMaxKb,
                'latitude' => 'nullable',
                'longitude' => 'nullable',
            ], [
                'photo.max' => __('Ukuran foto terlalu besar. Maksimal :max KB.', ['max' => $photoMaxKb]),
                'photo.required' => __('Foto selfie wajib diunggah untuk absensi.'),
            ]);

            if (!$request->latitude || !$request->longitude) {
                if (!$request->hasFile('photo')) {
                    return back()->withErrors(['message' => __('GPS tidak terdeteksi. Silakan ambil foto sebagai bukti kehadiran.')]);
                }
            }

            $deviceFingerprint = $this->attendanceService->resolveAttendanceDeviceFingerprint($request, $currentUser);
            $currentUserAgent = mb_substr((string)$request->userAgent(), 0, 255);
            if (!$currentUser?->attendance_device_hash || (string)$currentUser?->attendance_device_hash !== $deviceFingerprint) {
                $currentUser?->forceFill([
                    'attendance_device_hash' => $deviceFingerprint,
                    'attendance_device_locked_at' => now(),
                ])->save();
            }

            $officeLat = Setting::getValue('attendance_office_lat');
            $officeLng = Setting::getValue('attendance_office_lng');
            $radius = Setting::getValue('attendance_radius', 100);

            if ($officeLat && $officeLng && $request->latitude && $request->longitude) {
                $distance = $this->attendanceService->calculateDistance($request->latitude, $request->longitude, $officeLat, $officeLng);
                if ($distance > $radius) {
                    return back()->withErrors(['message' => __('You are too far from the office. Distance: :dist m. Max: :max m.', ['dist' => round($distance), 'max' => $radius])]);
                }
            }

            $path = $request->hasFile('photo')
                ? $request->file('photo')->store('attendance-photos', 'public')
                : null;

            $oldValues = $attendance->toArray();

            $attendance->update([
                'clock_out' => now(),
                'photo_clock_out' => $path,
                'lat_clock_out' => $request->latitude ?: null,
                'lng_clock_out' => $request->longitude ?: null,
                'device_fingerprint_clock_out' => $deviceFingerprint,
                'ip_clock_out' => (string)($request->ip() ?? ''),
                'user_agent_clock_out' => $currentUserAgent,
                'notes' => ($attendance->notes ?? '') . "\nClock Out Note: " . $request->notes,
            ]);

            \App\Models\AuditLog::log(
                'clock_out',
                $attendance,
                $oldValues,
                $attendance->toArray(),
                'Check-out absensi untuk ' . (Auth::user()?->name ?? 'Unknown')
            );

            if ($currentUser) {
                SendClockOutNotificationJob::dispatch($currentUser, $attendance)->afterResponse();
            }

            return redirect()->route($this->attendanceService->attendanceRedirectRoute($request))->with('success', __('Clock Out successful!'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Attendance Update Fatal Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            return back()->withErrors(['message' => __('Terjadi kesalahan saat memproses absensi pulang: ') . $e->getMessage()]);
        }
    }

    public function destroy(TechnicianAttendance $attendance)
    {
        if (!Auth::user()?->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $oldValues = $attendance->toArray();
        $userName = $attendance->user?->name ?? 'Unknown User';

        if ($attendance->photo_clock_in) {
            Storage::disk('public')->delete($attendance->photo_clock_in);
        }
        if ($attendance->photo_clock_out) {
            Storage::disk('public')->delete($attendance->photo_clock_out);
        }
        $attendance->delete();

        \App\Models\AuditLog::log(
            'delete',
            null,
            $oldValues,
            [],
            'Menghapus absensi untuk ' . $userName . ' oleh ' . (Auth::user()?->name ?? 'Unknown')
        );

        return back()->with('success', __('Attendance record deleted.'));
    }

    public function bulkDestroy(Request $request)
    {
        if (!Auth::user()?->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:technician_attendances,id',
        ]);

        $attendances = TechnicianAttendance::whereIn('id', $request->ids)->get();

        foreach ($attendances as $attendance) {
            if ($attendance->photo_clock_in) {
                Storage::disk('public')->delete($attendance->photo_clock_in);
            }
            if ($attendance->photo_clock_out) {
                Storage::disk('public')->delete($attendance->photo_clock_out);
            }
            $attendance->delete();
        }

        return back()->with('success', __('Selected attendance records deleted.'));
    }
}
