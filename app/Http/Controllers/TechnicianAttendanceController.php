<?php

namespace App\Http\Controllers;

use App\Jobs\Attendance\SendClockInNotificationJob;
use App\Jobs\Attendance\SendClockOutNotificationJob;
use App\Jobs\Attendance\SendKioskClockInNotificationJob;
use App\Jobs\Attendance\SendKioskClockOutNotificationJob;
use App\Jobs\Attendance\SendManualAttendanceNotificationJob;
use App\Models\LeaveRequest;
use App\Models\SalaryAdjustment;
use App\Models\Setting;
use App\Models\TechnicianDailySchedule;
use App\Models\TechnicianAttendance;
use App\Models\TechnicianSchedule;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WashEmployee;
use App\Models\Role;
use App\Traits\HasAttendanceFilters;
use App\Traits\SendsNotifications;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class TechnicianAttendanceController extends Controller implements HasMiddleware
{
    use SendsNotifications, HasAttendanceFilters;

    protected function canViewAllAttendanceData(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        
        return $user->hasAnyRole(['admin', 'finance', 'direktur', 'manager hrd', 'owner', 'owner pendiri', 'leader']);
    }

    protected function isAdminOrHrdManager(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        
        return $user->hasAnyRole(['admin', 'manager hrd']);
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
        if ($this->isUserCoordinator($user)) {
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

        $usersQuery = User::whereHas('role', function ($q) {
            $q->whereNotIn('name', [Role::CUSTOMER, Role::COORDINATOR]);
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

        return view('technicians.attendance.daily', compact('users', 'attendancesByDate', 'dates', 'startDate', 'endDate', 'status', 'search'));
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if ($this->isUserCoordinator($user)) {
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

        $techniciansQuery = User::whereHas('role', function ($q) {
            $q->whereNotIn('name', [Role::CUSTOMER, Role::COORDINATOR]);
        })->where('is_active', true)
          ->with('role');

        if (! $this->canViewAllAttendanceData()) {
            $techniciansQuery->where('id', Auth::id());
        }

        $technicians = $techniciansQuery->orderBy('name')->get();

        return view('technicians.attendance.index', compact('attendances', 'technicians', 'stats'));
    }

    public function payslip(Request $request)
    {
        $user = Auth::user();
        if ($this->isUserCoordinator($user)) {
            abort(403, 'Anda tidak diizinkan mengakses halaman ini.');
        }
        
        $techniciansQuery = User::whereHas('role', function ($q) {
            $q->whereNotIn('name', [Role::CUSTOMER, Role::COORDINATOR]);
        })->where('is_active', true)
          ->with('role');

        if (! $this->canViewAllAttendanceData()) {
            $techniciansQuery->where('id', Auth::id());
        }

        $technicians = $techniciansQuery->orderBy('name')->get();
        
        $attendances = $this->getFilteredAttendanceQuery($request)->oldest('clock_in')->get();
        $allAdjustments = $this->getFilteredAdjustmentsQuery($request)->get()->groupBy('user_id');

        $summary = $this->calculateAttendanceSummary($attendances, $allAdjustments, $request);

        return view('technicians.attendance.payslip', compact('summary', 'request', 'technicians'));
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        if ($this->isUserCoordinator($user)) {
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

            $users = User::whereHas('role', function ($q) {
                $q->whereNotIn('name', [Role::CUSTOMER, Role::COORDINATOR]);
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

            return response()->streamDownload(function () use ($users, $attendancesByDate, $dates, $startDate, $endDate) {
                $writer = new Writer;
                $writer->openToFile('php://output');

                $writer->addRow(Row::fromValues(['RINCIAN KEHADIRAN KARYAWAN']));
                $writer->addRow(Row::fromValues(['Periode: ' . Carbon::parse($startDate)->translatedFormat('d F Y') . ' - ' . Carbon::parse($endDate)->translatedFormat('d F Y')]));
                $writer->addRow(Row::fromValues(['Tanggal Cetak: ' . now()->translatedFormat('d F Y H:i')]));
                $writer->addRow(Row::fromValues([]));
                $writer->addRow(Row::fromValues([
                    'Nama Karyawan',
                    'Tanggal',
                    'Jam Masuk',
                    'Jam Pulang',
                    'Status',
                    'Catatan',
                ]));

                foreach ($dates as $dateStr) {
                    foreach ($users as $user) {
                        $isOff = $this->isUserOffOnDate($user, $dateStr);
                        $attendances = $attendancesByDate->get($dateStr, collect());
                        $attendance = $attendances->get($user->id);

                        if ($isOff) {
                            $status = __('OFF');
                            $clockIn = '-';
                            $clockOut = '-';
                            $notes = '-';
                        } elseif ($attendance) {
                            $status = __(ucfirst($attendance->status));
                            $clockIn = $attendance->clock_in?->format('H:i') ?? '-';
                            $clockOut = $attendance->clock_out?->format('H:i') ?? '-';
                            $notes = $attendance->notes ?? '-';
                        } else {
                            $status = __('Belum Absen');
                            $clockIn = '-';
                            $clockOut = '-';
                            $notes = '-';
                        }

                        $writer->addRow(Row::fromValues([
                            $user->name,
                            Carbon::parse($dateStr)->translatedFormat('d F Y'),
                            $clockIn,
                            $clockOut,
                            $status,
                            $notes,
                        ]));
                    }
                }

                $writer->close();
            }, 'rincian_kehadiran_harian_' . now()->format('Y-m-d_His') . '.xlsx');
        }

        $attendances = $this->getFilteredAttendanceQuery($request)->oldest('clock_in')->get();
        
        if ($request->query('download') === 'details') {
            return response()->streamDownload(function () use ($attendances) {
                $writer = new Writer;
                $writer->openToFile('php://output');

                $writer->addRow(Row::fromValues(['RINCIAN KEHADIRAN KARYAWAN']));
                $writer->addRow(Row::fromValues(['Tanggal Cetak: ' . now()->translatedFormat('d F Y H:i')]));
                $writer->addRow(Row::fromValues([]));
                $writer->addRow(Row::fromValues([
                    'Nama Karyawan',
                    'Tanggal',
                    'Jam Masuk',
                    'Jam Pulang',
                    'Status',
                    'Catatan',
                ]));

                foreach ($attendances as $attendance) {
                    $writer->addRow(Row::fromValues([
                        $attendance->user?->name ?? '-',
                        ($attendance->work_date ?? $attendance->clock_in)?->translatedFormat('d F Y') ?? '-',
                        $attendance->clock_in?->format('H:i') ?? '-',
                        $attendance->clock_out?->format('H:i') ?? '-',
                        __(ucfirst($attendance->status)),
                        $attendance->notes ?? '-',
                    ]));
                }

                $writer->close();
            }, 'rincian_kehadiran_' . now()->format('Y-m-d_His') . '.xlsx');
        }

        $allAdjustments = $this->getFilteredAdjustmentsQuery($request)->get()->groupBy('user_id');

        $summary = $this->calculateAttendanceSummary($attendances, $allAdjustments, $request);

        return response()->streamDownload(function () use ($summary) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues(['REKAP GAJI TEKNISI']));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues([
                'Nama Teknisi',
                'Total Hadir',
                'Total Cuti/Izin/Sakit',
                'Total Hari Dibayar',
                'Gaji Harian',
                'Total Bonus',
                'Total Kasbon',
                'Total Gaji',
            ]));

            foreach ($summary as $data) {
                $writer->addRow(Row::fromValues([
                    $data['user']->name,
                    $data['present_count'],
                    $data['leave_count'],
                    $data['paid_days'],
                    $data['daily_salary'],
                    $data['total_bonus'],
                    $data['total_kasbon'],
                    $data['total_salary'],
                ]));
            }

            $writer->addNewSheetAndMakeItCurrent();
            $writer->addRow(Row::fromValues(['DETAIL ABSENSI']));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues([
                'Nama Teknisi',
                'Tanggal',
                'Jam Masuk',
                'Jam Pulang',
                'Status',
                'Catatan',
            ]));

            foreach ($summary as $data) {
                foreach ($data['dates'] as $attendance) {
                    $writer->addRow(Row::fromValues([
                        $data['user']->name,
                        ($attendance->work_date ?? $attendance->clock_in)?->translatedFormat('d F Y') ?? '-',
                        $attendance->clock_in?->format('H:i') ?? '-',
                        $attendance->clock_out?->format('H:i') ?? '-',
                        __(ucfirst($attendance->status)),
                        $attendance->notes ?? '-',
                    ]));
                }
            }

            $writer->close();
        }, 'rekap_teknisi_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function recapToFinance(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $this->isAdminOrHrdManager();
        
        if (!$isAdmin) {
            abort(403, 'Unauthorized');
        }

        $attendances = $this->getFilteredAttendanceQuery($request)->oldest('clock_in')->get();
        $pendingAdjustments = $this->getFilteredAdjustmentsQuery($request, 'pending')->get();
        $adjustmentsByUser = $pendingAdjustments->groupBy('user_id');

        if ($attendances->isEmpty() && $pendingAdjustments->isEmpty()) {
            return back()->with('error', __('No attendance records or pending adjustments found for the selected period.'));
        }

        $userIds = $attendances->pluck('user_id')->merge($pendingAdjustments->pluck('user_id'))->unique();
        $totalAmount = 0;

        foreach ($userIds as $userId) {
            $userItems = $attendances->where('user_id', $userId);
            $userAdjustments = $adjustmentsByUser->get($userId, collect());

            $attendanceSalary = 0;
            if ($userItems->isNotEmpty()) {
                $user = $userItems->first()?->user;
                if (!$user) continue;
                
                $employee = $user->employee;
                $presentCount = $userItems->whereIn('status', ['present', 'late'])->count();
                $leaveCount = $userItems->whereIn('status', ['leave', 'permit', 'sick'])->count();
                $paidDays = $presentCount + $leaveCount;
                
                $workingDays = (int) Setting::getValue('attendance_working_days', 28);
                $employeeDailySalary = $employee?->daily_salary ?? 0;
                $employeeMonthlySalary = $employee?->monthly_salary ?? 0;
                
                if ($employeeDailySalary > 0) {
                    $dailySalary = $employeeDailySalary;
                } elseif ($employeeMonthlySalary > 0) {
                    $dailySalary = $employeeMonthlySalary / $workingDays;
                } elseif ($user->daily_salary > 0) {
                    $dailySalary = $user->daily_salary;
                } elseif ($user->monthly_salary > 0) {
                    $dailySalary = $user->monthly_salary / $workingDays;
                } else {
                    $dailySalary = 0;
                }
                
                $attendanceSalary = $paidDays * $dailySalary;
            }

            $bonus = $userAdjustments->where('type', 'bonus')->sum('amount');
            $kasbon = $userAdjustments->where('type', 'kasbon')->sum('amount');

            $totalAmount += ($attendanceSalary + $bonus - $kasbon);
        }

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
            $user = User::find($request->user_id);
            if ($user) {
                $description .= ' - ' . $user->name;
            }
        }

        Transaction::create([
            'user_id' => Auth::id(),
            'type' => 'expense',
            'category' => 'Salary',
            'amount' => $totalAmount,
            'description' => $description,
            'transaction_date' => now(),
        ]);

        if ($pendingAdjustments->isNotEmpty()) {
            SalaryAdjustment::whereIn('id', $pendingAdjustments->pluck('id'))->update(['status' => 'processed']);
        }

        return back()->with('success', __('Salary expense of :amount has been recorded in Finance.', ['amount' => number_format($totalAmount, 0, ',', '.')]));
    }

    public function sendNotification(TechnicianAttendance $attendance)
    {
        $user = Auth::user();
        $isAdmin = $this->isAdminOrHrdManager();
        
        if (!$isAdmin) {
            abort(403, 'Unauthorized');
        }

        $targetUser = $attendance->user;
        if (! $targetUser) {
            return back()->with('error', __('User not found.'));
        }

        $clockIn = $attendance->clock_in?->format('H:i') ?? '-';
        $clockOut = $attendance->clock_out?->format('H:i') ?? '-';
        $status = ucfirst($attendance->status);
        $date = $attendance->clock_in?->translatedFormat('d F Y') ?? '-';

        $message = "Halo {$targetUser->name},\n\nBerikut detail absensi Anda:\n📅 Tanggal: {$date}\n⏰ Masuk: {$clockIn}\n⏰ Pulang: {$clockOut}\n📊 Status: {$status}\n\nTerima kasih.";

        $sentCount = 0;
        $channels = [];

        if ($targetUser->phone) {
            $wa = new \App\Services\WhatsAppService;
            $wa->sendMessage($targetUser->phone, $message, 'attendance_notification');
            $sentCount++;
            $channels[] = 'WhatsApp';
        }

        if ($targetUser->telegram_chat_id) {
            $telegram = new \App\Services\TelegramService;
            $tgMessage = "🔔 *DETAIL ABSENSI*\n\n" .
                        "Halo *" . \App\Services\TelegramService::escape($targetUser->name) . "*,\n\n" .
                        "Berikut detail absensi Anda:\n" .
                        "📅 *Tanggal:* " . \App\Services\TelegramService::escape($date) . "\n" .
                        "⏰ *Masuk:* " . \App\Services\TelegramService::escape($clockIn) . "\n" .
                        "⏰ *Pulang:* " . \App\Services\TelegramService::escape($clockOut) . "\n" .
                        "📊 *Status:* *" . \App\Services\TelegramService::escape($status) . "*\n\n" .
                        "Terima kasih.";

            $telegram->sendMessage($targetUser->telegram_chat_id, $tgMessage);
            $sentCount++;
            $channels[] = 'Telegram';
        }

        if ($sentCount === 0) {
            return back()->with('error', __('User does not have a phone number or Telegram Chat ID.'));
        }

        $channelList = implode(' & ', $channels);
        return back()->with('success', __('Notification sent via :channels.', ['channels' => $channelList]));
    }

    public function storeManual(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $this->isAdminOrHrdManager();
        
        if (!$isAdmin) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required|in:present,late,leave,permit,sick,alpha',
            'clock_in_create' => 'nullable|date_format:H:i',
            'clock_out_create' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $exists = TechnicianAttendance::where('user_id', $request->user_id)
            ->where(function ($q) use ($request) {
                $q->whereDate('clock_in', $request->date)
                  ->orWhereDate('work_date', $request->date);
            })
            ->exists();

        if ($exists) {
            return back()->with('error', __('Attendance record for this user on this date already exists.'));
        }

        $data = [
            'user_id' => $request->user_id,
            'work_date' => $request->date,
            'status' => $request->status,
            'notes' => $request->notes,
        ];

        if ($request->clock_in_create) {
            $data['clock_in'] = $request->date . ' ' . $request->clock_in_create . ':00';
        } else {
            $data['clock_in'] = $request->date . ' 08:00:00';
        }

        if ($request->clock_out_create) {
            $data['clock_out'] = $request->date . ' ' . $request->clock_out_create . ':00';
        } else {
            $data['clock_out'] = $request->date . ' 17:00:00';
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

    public function updateManual(Request $request, $id)
    {
        $user = Auth::user();
        $isAdmin = $this->isAdminOrHrdManager();

        if (!$isAdmin) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'date'      => 'required|date',
            'status'    => 'required|in:present,late,leave,permit,sick,alpha',
            'clock_in'  => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            'notes'     => 'nullable|string',
        ]);

        $attendance = TechnicianAttendance::findOrFail($id);

        $oldValues = $attendance->toArray();

        $updateData = [
            'work_date' => $request->date,
            'status'    => $request->status,
            'notes'     => $request->notes,
        ];

        if ($request->clock_in) {
            $updateData['clock_in'] = $request->date . ' ' . $request->clock_in . ':00';
        }

        if ($request->clock_out) {
            $updateData['clock_out'] = $request->date . ' ' . $request->clock_out . ':00';
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
        if (! $this->isAttendanceEligibleUser(Auth::user())) {
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

        $clockInWindow = $this->resolveClockInWindow(Auth::user());
        $clockInStart = $clockInWindow['start'];
        $clockInEnd = $clockInWindow['end'];
        $clockOutStart = Setting::getValue('attendance_clock_out_start', '20:00');
        $clockOutEnd = Setting::getValue('attendance_clock_out_end', '01:00');
        $leaveQuota = Setting::getValue('technician_leave_quota', 3);
        $faceVerificationEnabled = (string) Setting::getValue('attendance_face_verification_enabled', '0');
        $shiftInfo = $this->resolveTodayShiftInfo(Auth::user());
        $attendanceOfficeLat = (float) Setting::getValue('attendance_office_lat', Setting::getValue('office_latitude', 0));
        $attendanceOfficeLng = (float) Setting::getValue('attendance_office_lng', Setting::getValue('office_longitude', 0));
        $attendanceRadius = (float) Setting::getValue('attendance_radius', Setting::getValue('attendance_max_distance_meters', 100));
        $attendancePhotoRequired = $this->isAttendancePhotoRequired();

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

        $cardCode = trim((string) $payload['card_code']);
        $user = $this->resolveAttendanceUser($cardCode);

        if (! $user) {
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

        if (! $todayAttendance) {
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

            $clockInWindow = $this->resolveClockInWindow($user);
            $clockInStart = $clockInWindow['start'];
            $clockInEnd = $clockInWindow['end'];
            $shiftCutoff = $clockInWindow['shift_cutoff'];
            $currentTime = now()->format('H:i');
            if (! $this->isTimeWithinRange($currentTime, $clockInStart, $clockInEnd)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Clock In only allowed between :start - :end WIB.', ['start' => $clockInStart, 'end' => $clockInEnd]),
                ], 422);
            }

            $allowAfterCutoff = (bool) Setting::getValue('attendance_allow_after_cutoff', false);
            if (!$allowAfterCutoff && $this->isPastCutoffTime($shiftCutoff)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Batas waktu absensi masuk telah berakhir. Status kehadiran Anda akan dicatat sebagai Alpha.'),
                ], 422);
            }

            $status = $this->determineClockInStatus((string) ($clockInWindow['official_start'] ?? $clockInStart), (string) ($clockInWindow['shift_cutoff'] ?? $clockInEnd));

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

        $cooldownMinutes = (int) Setting::getValue('attendance_kiosk_cooldown_minutes', 5);
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

    public function store(Request $request)
    {
        if (! $this->isAttendanceEligibleUser(Auth::user())) {
            return redirect()->route($this->attendanceRedirectRoute($request))->withErrors([
                'message' => __('Role Anda tidak diizinkan untuk absensi.'),
            ]);
        }

        $lockKey = 'attendance-clock-in-' . Auth::id();
        $lock = Cache::lock($lockKey, 10);

        if (! $lock->get()) {
            return back()->withErrors(['message' => __('Permintaan absensi sedang diproses. Mohon tunggu sebentar.')]);
        }

        try {
            $clockInWindow = $this->resolveClockInWindow(Auth::user());
            $clockInStart = $clockInWindow['start'];
            $clockInEnd = $clockInWindow['end'];
            $shiftCutoff = $clockInWindow['shift_cutoff'];

            $now = now();
            $currentTime = $now->format('H:i');

            if (! $this->isTimeWithinRange($currentTime, $clockInStart, $clockInEnd)) {
                return back()->withErrors(['message' => __('Clock In only allowed between :start - :end WIB.', ['start' => $clockInStart, 'end' => $clockInEnd])]);
            }

            if ($this->isPastCutoffTime($shiftCutoff, $now)) {
                return back()->withErrors(['message' => __('Batas waktu absensi masuk telah berakhir. Status kehadiran Anda telah dicatat sebagai Alpha.')]);
            }

            $today = today();

            $alreadyClockedInToday = TechnicianAttendance::where('user_id', Auth::id())
                ->where(function ($q) use ($today) {
                    $q->whereDate('clock_in', $today->toDateString())
                      ->orWhereDate('work_date', $today->toDateString());
                })
                ->exists();

            if ($alreadyClockedInToday) {
                return redirect()->route($this->attendanceRedirectRoute($request))->withErrors(['message' => __('Gagal: Anda sudah melakukan absen masuk hari ini.')]);
            }

            $hasLeaveRequest = LeaveRequest::where('user_id', Auth::id())
                ->where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->exists();

            if ($hasLeaveRequest) {
                return back()->withErrors(['message' => __('Gagal: Anda sedang dalam masa cuti/izin hari ini.')]);
            }

            $photoMaxKb = $this->resolveAttendancePhotoMaxKb();
            $photoRequired = $this->isAttendancePhotoRequired();
            $photoRule = $photoRequired ? 'required' : 'nullable';

            $request->validate([
                'photo' => $photoRule . '|image|max:' . $photoMaxKb,
                'latitude' => 'nullable',
                'longitude' => 'nullable',
                'device_fingerprint' => 'nullable|string|min:8|max:128',
            ], [
                'photo.max' => __('Ukuran foto terlalu besar. Maksimal :max KB.', ['max' => $photoMaxKb]),
                'photo.required' => __('Foto selfie wajib diunggah untuk absensi.'),
            ]);

            if (! $request->latitude || ! $request->longitude) {
                if (! $request->hasFile('photo')) {
                    return back()->withErrors(['message' => __('GPS tidak terdeteksi. Silakan ambil foto sebagai bukti kehadiran.')]);
                }
            }

            $deviceFingerprint = $this->resolveAttendanceDeviceFingerprint($request);
            $currentUser = Auth::user();
            if (! $currentUser?->attendance_device_hash) {
                $currentUser?->forceFill([
                    'attendance_device_hash' => $deviceFingerprint,
                    'attendance_device_locked_at' => now(),
                ])->save();
            } elseif ((string) $currentUser?->attendance_device_hash !== $deviceFingerprint) {
                $currentUser?->forceFill([
                    'attendance_device_hash' => $deviceFingerprint,
                    'attendance_device_locked_at' => now(),
                ])->save();
            }

            $officeLat = Setting::getValue('attendance_office_lat');
            $officeLng = Setting::getValue('attendance_office_lng');
            $radius = Setting::getValue('attendance_radius', 100);

            if ($officeLat && $officeLng && $request->latitude && $request->longitude) {
                $distance = $this->calculateDistance($request->latitude, $request->longitude, $officeLat, $officeLng);
                if ($distance > $radius) {
                    return back()->withErrors(['message' => __('You are too far from the office. Distance: :dist m. Max: :max m.', ['dist' => round($distance), 'max' => $radius])]);
                }
            }

            $path = $request->hasFile('photo')
                ? $request->file('photo')->store('attendance-photos', 'public')
                : null;

            $attendance = TechnicianAttendance::create([
                'user_id' => Auth::id(),
                'work_date' => today()->toDateString(),
                'clock_in' => now(),
                'photo_clock_in' => $path,
                'lat_clock_in' => $request->latitude ?: null,
                'lng_clock_in' => $request->longitude ?: null,
                'device_fingerprint_clock_in' => $deviceFingerprint,
                'ip_clock_in' => (string) ($request->ip() ?? ''),
                'user_agent_clock_in' => mb_substr((string) $request->userAgent(), 0, 255),
                'status' => $this->determineClockInStatus((string) ($clockInWindow['official_start'] ?? $clockInStart), (string) ($clockInWindow['shift_cutoff'] ?? $clockInEnd), $now),
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

            return redirect()->route($this->attendanceRedirectRoute($request))->with('success', __('Clock In successful!'));
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

    public function update(Request $request, $id)
    {
        if (! $this->isAttendanceEligibleUser(Auth::user())) {
            return redirect()->route($this->attendanceRedirectRoute($request))->withErrors([
                'message' => __('Role Anda tidak diizinkan untuk absensi.'),
            ]);
        }

        try {
            $clockOutStart = Setting::getValue('attendance_clock_out_start', '20:00');
            $clockOutEnd = Setting::getValue('attendance_clock_out_end', '01:00');

            $now = now();
            $currentTime = $now->format('H:i');

            $isAllowed = false;
            if ($clockOutStart > $clockOutEnd) {
                $isAllowed = ($currentTime >= $clockOutStart || $currentTime <= $clockOutEnd);
            } else {
                $isAllowed = ($currentTime >= $clockOutStart && $currentTime <= $clockOutEnd);
            }

            if (! $isAllowed) {
                return back()->withErrors(['message' => __('Clock Out only allowed between :start - :end WIB.', ['start' => $clockOutStart, 'end' => $clockOutEnd])]);
            }

            $attendance = TechnicianAttendance::where('user_id', Auth::id())->findOrFail($id);

            $cooldownMinutes = (int) Setting::getValue('attendance_cooldown_minutes', 30);
            $diffInMinutes = $attendance->clock_in?->diffInMinutes(now()) ?? 0;
            if ($diffInMinutes < $cooldownMinutes) {
                return back()->withErrors(['message' => __('Gagal: Jeda waktu absen masuk dan pulang minimal :min menit. Baru :diff menit berlalu.', [
                    'min' => $cooldownMinutes,
                    'diff' => $diffInMinutes,
                ])]);
            }

            $photoMaxKb = $this->resolveAttendancePhotoMaxKb();
            $photoRequired = $this->isAttendancePhotoRequired();
            $photoRule = $photoRequired ? 'required' : 'nullable';
            
            $request->validate([
                'photo' => $photoRule . '|image|max:' . $photoMaxKb,
                'latitude' => 'nullable',
                'longitude' => 'nullable',
                'device_fingerprint' => 'nullable|string|min:8|max:128',
            ], [
                'photo.max' => __('Ukuran foto terlalu besar. Maksimal :max KB.', ['max' => $photoMaxKb]),
                'photo.required' => __('Foto selfie wajib diunggah untuk absensi.'),
            ]);

            if (! $request->latitude || ! $request->longitude) {
                if (! $request->hasFile('photo')) {
                    return back()->withErrors(['message' => __('GPS tidak terdeteksi. Silakan ambil foto sebagai bukti kehadiran.')]);
                }
            }

            $deviceFingerprint = $this->resolveAttendanceDeviceFingerprint($request);
            $currentUser = Auth::user();
            $currentUserAgent = mb_substr((string) $request->userAgent(), 0, 255);
            if (! $currentUser?->attendance_device_hash || (string) $currentUser?->attendance_device_hash !== $deviceFingerprint) {
                $currentUser?->forceFill([
                    'attendance_device_hash' => $deviceFingerprint,
                    'attendance_device_locked_at' => now(),
                ])->save();
            }

            $officeLat = Setting::getValue('attendance_office_lat');
            $officeLng = Setting::getValue('attendance_office_lng');
            $radius = Setting::getValue('attendance_radius', 100);

            if ($officeLat && $officeLng && $request->latitude && $request->longitude) {
                $distance = $this->calculateDistance($request->latitude, $request->longitude, $officeLat, $officeLng);
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
                'ip_clock_out' => (string) ($request->ip() ?? ''),
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

            return redirect()->route($this->attendanceRedirectRoute($request))->with('success', __('Clock Out successful!'));
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
        if (! Auth::user()?->hasRole('admin')) {
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
        if (! Auth::user()?->hasRole('admin')) {
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

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
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

    private function attendanceRedirectRoute(Request $request): string
    {
        return $request->routeIs('landing.attendance.*') ? 'landing' : 'attendance.create';
    }

    private function resolveAttendancePhotoMaxKb(): int
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

    private function isAttendancePhotoRequired(): bool
    {
        $value = Setting::getValue('attendance_photo_required', '1');

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function resolveAttendanceDeviceFingerprint(Request $request): string
    {
        $rawFingerprint = trim((string) $request->input('device_fingerprint', ''));
        if ($rawFingerprint !== '') {
            return $rawFingerprint;
        }

        $fallbackPayload = implode('|', [
            (string) Auth::id(),
            mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        return hash('sha256', $fallbackPayload);
    }

    private function resolveAttendanceUser(string $cardCode): ?User
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
                    $q->orWhere('id', (int) $code);
                }
            })
            ->first();
    }

    private function attendanceEligibleRoleNames(): array
    {
        return [
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
    }

    private function isUserCoordinator(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        
        return $user->hasRole(Role::COORDINATOR);
    }

    private function isAttendanceEligibleUser(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        
        if ($this->isUserCoordinator($user)) {
            return false;
        }
        
        $excludedRoles = [Role::CUSTOMER, Role::DIREKTUR, 'owner', Role::COORDINATOR];
        return ! $user->hasAnyRole($excludedRoles);
    }

    private function isWithinClockInWindow(): bool
    {
        $clockInStart = Setting::getValue('attendance_clock_in_start', '07:00');
        $clockInEnd = Setting::getValue('attendance_clock_in_end', '13:00');
        $currentTime = now()->format('H:i');

        return $this->isTimeWithinRange($currentTime, $clockInStart, $clockInEnd);
    }

    private function isWithinClockOutWindow(): bool
    {
        $clockOutStart = Setting::getValue('attendance_clock_out_start', '20:00');
        $clockOutEnd = Setting::getValue('attendance_clock_out_end', '01:00');
        $currentTime = now()->format('H:i');

        return $this->isTimeWithinRange($currentTime, $clockOutStart, $clockOutEnd);
    }

    private function resolveClockInWindow(User $user): array
    {
        $globalStart = Setting::getValue('attendance_clock_in_start', '07:00');
        $globalEnd = Setting::getValue('attendance_clock_in_end', '13:00');
        $earlyMinutes = (int) Setting::getValue('attendance_clock_in_early_minutes', 60);
        if ($earlyMinutes < 0) {
            $earlyMinutes = 0;
        }
        $shiftInfo = $this->resolveTodayShiftInfo($user);

        $isWorkShift = in_array($shiftInfo['status'] ?? '', [TechnicianSchedule::STATUS_PIKET, TechnicianSchedule::STATUS_BACKUP, TechnicianSchedule::STATUS_LONGSHIFT], true);
        $hasShiftTime = ! empty($shiftInfo['shift_start']) && $shiftInfo['shift_start'] !== '-';

        if ($isWorkShift && $hasShiftTime) {
            $officialStart = (string) $shiftInfo['shift_start'];
            $effectiveStart = $this->subtractMinutesFromTime($officialStart, $earlyMinutes);
            $shiftCutoff = (string) ($shiftInfo['shift_cutoff'] ?? $globalEnd);

            return [
                'start' => $effectiveStart,
                'end' => (string) ($shiftInfo['shift_end'] ?? $globalEnd),
                'official_start' => $officialStart,
                'shift_cutoff' => $shiftCutoff,
            ];
        }

        $globalStartText = (string) $globalStart;
        return [
            'start' => $this->subtractMinutesFromTime($globalStartText, $earlyMinutes),
            'end' => (string) $globalEnd,
            'official_start' => $globalStartText,
            'shift_cutoff' => $globalEnd,
        ];
    }

    private function determineClockInStatus(string $clockInStart, string $shiftCutoff, ?Carbon $now = null): string
    {
        $checkTime = ($now ?? now())->copy()->timezone(config('app.timezone', 'Asia/Jakarta'));
        $lateTolerance = (int) Setting::getValue('attendance_late_tolerance', 0);
        $lateTolerance = max(0, $lateTolerance);

        $clockInStartMinutes = $this->timeToMinutes($clockInStart, 8 * 60);
        $currentMinutes = ((int) $checkTime->format('H') * 60) + (int) $checkTime->format('i');
        $lateThreshold = min((23 * 60) + 59, $clockInStartMinutes + $lateTolerance);

        return $currentMinutes > $lateThreshold ? 'late' : 'present';
    }

    private function isPastCutoffTime(string $shiftCutoff, ?Carbon $now = null): bool
    {
        $checkTime = ($now ?? now())->copy()->timezone(config('app.timezone', 'Asia/Jakarta'));
        $currentMinutes = ((int) $checkTime->format('H') * 60) + (int) $checkTime->format('i');
        $cutoffMinutes = $this->timeToMinutes($shiftCutoff, 10 * 60);
        return $currentMinutes > $cutoffMinutes;
    }

    private function isTimeWithinRange(string $currentTime, string $startTime, string $endTime): bool
    {
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        }

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    private function subtractMinutesFromTime(string $time, int $minutes): string
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

    private function timeToMinutes(string $time, int $default): int
    {
        $time = preg_replace('/[^0-9:]/', '', $time);
        
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            return $default;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];

        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return $default;
        }

        return ($hours * 60) + $minutes;
    }

    private function resolveTodayShiftInfo(User $user): array
    {
        $group = $this->resolveScheduleGroup($user);
        $status = null;
        $source = 'default';
        $today = now();

        $roleName = strtolower((string) ($user->role?->name ?? ''));
        $isExcludedFromSchedule = in_array($roleName, [Role::DIREKTUR, Role::COORDINATOR], true);

        if (! $isExcludedFromSchedule) {
            if (Schema::hasTable('technician_daily_schedules')) {
                $daily = TechnicianDailySchedule::query()
                    ->where('user_id', $user->id)
                    ->whereDate('date', $today->toDateString())
                    ->first();
                if ($daily) {
                    $status = (string) $daily->status;
                    $source = 'daily';
                }
            }

            if ($status === null && Schema::hasTable('technician_schedules')) {
                $weekYear = (int) $today->copy()->weekYear;
                $weekly = TechnicianSchedule::query()
                    ->where('user_id', $user->id)
                    ->where('year', $weekYear)
                    ->where('week_number', $today->weekOfYear)
                    ->first();
                if ($weekly) {
                    $status = (string) $weekly->status;
                    $source = 'weekly';
                }
            }
        }

        if (! in_array($status, [TechnicianSchedule::STATUS_PIKET, TechnicianSchedule::STATUS_BACKUP, TechnicianSchedule::STATUS_LONGSHIFT, TechnicianSchedule::STATUS_OFF], true)) {
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
            $shiftStart = (string) $shiftConfig['longshift_start'];
            $shiftEnd = (string) $shiftConfig['longshift_end'];
            $shiftCutoff = (string) $shiftConfig['longshift_cutoff'];
        } elseif ($status === TechnicianSchedule::STATUS_PIKET) {
            $settingKey = $group === 'wash' ? 'weekly_schedule_wash' : 'weekly_schedule_teknisi';
            $scheduleRaw = (string) Setting::getValue($settingKey, '{}');
            $schedule = json_decode($scheduleRaw, true);
            $dayName = $today->englishDayOfWeek;
            
            $dayConfig = $schedule[$dayName] ?? null;
            $isShiftEnabled = !empty($dayConfig['enabled']);
            $mappedShift = $isShiftEnabled ? ($dayConfig['shift'] ?? 'shift1') : 'shift1';

            if ($mappedShift === 'longshift') {
                $shiftLabel = 'Longshift';
                $shiftStart = (string) $shiftConfig['longshift_start'];
                $shiftEnd = (string) $shiftConfig['longshift_end'];
                $shiftCutoff = (string) $shiftConfig['longshift_cutoff'];
            } elseif ($mappedShift === 'shift2') {
                $shiftLabel = 'Shift 2';
                $shiftStart = (string) $shiftConfig['shift_2_start'];
                $shiftEnd = (string) $shiftConfig['shift_2_end'];
                $shiftCutoff = (string) $shiftConfig['shift_2_cutoff'];
            } else {
                $shiftLabel = 'Shift 1';
                $shiftStart = (string) $shiftConfig['shift_1_start'];
                $shiftEnd = (string) $shiftConfig['shift_1_end'];
                $shiftCutoff = (string) $shiftConfig['shift_1_cutoff'];
            }
        } elseif ($status === TechnicianSchedule::STATUS_BACKUP) {
            $shiftLabel = 'Shift 2';
            $shiftStart = (string) $shiftConfig['shift_2_start'];
            $shiftEnd = (string) $shiftConfig['shift_2_end'];
            $shiftCutoff = (string) $shiftConfig['shift_2_cutoff'];
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
        $roleName = strtolower((string) ($user->role?->name ?? ''));
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
                $status = (string) $daily->status;
            }
        }
        
        if ($status === null && Schema::hasTable('technician_schedules')) {
            $weekYear = (int) $date->copy()->weekYear;
            $weekly = TechnicianSchedule::query()
                ->where('user_id', $user->id)
                ->where('year', $weekYear)
                ->where('week_number', $date->weekOfYear)
                ->first();
            if ($weekly) {
                $status = (string) $weekly->status;
            }
        }
        
        return $status === TechnicianSchedule::STATUS_OFF;
    }

    private function attendanceShiftConfig(string $group): array
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

    private function isTodayLongshift(string $group): bool
    {
        $settingKey = $group === 'wash' ? 'weekly_schedule_wash' : 'weekly_schedule_teknisi';
        $scheduleRaw = (string) Setting::getValue($settingKey, '{}');
        $schedule = json_decode($scheduleRaw, true);
        if (! is_array($schedule)) {
            return false;
        }

        $todayKey = now()->englishDayOfWeek;
        $todaySchedule = $schedule[$todayKey] ?? null;
        if (! is_array($todaySchedule)) {
            return false;
        }

        $isEnabled = ! empty($todaySchedule['enabled']);
        $shift = (string) ($todaySchedule['shift'] ?? 'shift1');

        return $isEnabled && $shift === 'longshift';
    }

    private function resolveScheduleGroup(User $user): string
    {
        $roleName = strtolower((string) ($user->role?->name ?? ''));
        if (in_array($roleName, ['kasir-wash', 'karyawan-wash'], true)) {
            return 'wash';
        }

        if (Schema::hasTable('wash_employees') && WashEmployee::query()->where('user_id', $user->id)->exists()) {
            return 'wash';
        }

        return 'teknisi';
    }
}
