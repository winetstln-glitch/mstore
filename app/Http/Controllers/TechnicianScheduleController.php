<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\SchedulePeriod;
use App\Models\Setting;
use App\Models\TechnicianSchedule;
use App\Models\User;
use App\Models\WashEmployee;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class TechnicianScheduleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:schedule.view', only: ['index', 'exportPdf', 'exportExcel']),
            new Middleware('permission:schedule.manage', only: ['updatePeriod', 'store']),
        ];
    }

    public function index(Request $request)
    {
        $this->ensureShiftSettings();
        $this->ensureScheduleUsers();
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $techniciansQuery = $this->scheduleUsersQuery();

        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            $techniciansQuery->where('id', Auth::id());
        }

        $technicians = $techniciansQuery
            ->orderBy('name')
            ->get();
        $this->applyScheduleDisplayNames($technicians);
        $technicians = $this->deduplicateScheduleUsers($technicians);

        $totalTechnicians = $technicians->count();
        $columnsPerPage = (int) $request->input('per_page', 12);
        if ($columnsPerPage < 5) {
            $columnsPerPage = 5;
        }
        if ($columnsPerPage > 40) {
            $columnsPerPage = 40;
        }

        $columnPageCount = max(1, (int) ceil($totalTechnicians / max(1, $columnsPerPage)));
        $columnPage = (int) $request->input('col_page', 1);
        if ($columnPage < 1) {
            $columnPage = 1;
        }
        if ($columnPage > $columnPageCount) {
            $columnPage = $columnPageCount;
        }

        $columnStart = $totalTechnicians > 0 ? (($columnPage - 1) * $columnsPerPage) + 1 : 0;
        $columnEnd = min($totalTechnicians, $columnPage * $columnsPerPage);
        $technicians = $technicians
            ->slice(($columnPage - 1) * $columnsPerPage, $columnsPerPage)
            ->values();

        // Get schedules for the selected month (spanning weeks)
        // Simple logic: get schedules where week_number falls in the month
        // Or simpler: just get all schedules for the year and filter in view
        $schedules = TechnicianSchedule::where('year', $year)
            ->with('user')
            ->get()
            ->groupBy('week_number');

        $periods = SchedulePeriod::where('year', $year)
            ->get()
            ->keyBy('week_number');

        $shift1Start = Setting::getValue('attendance_shift_1_start', '08:00');
        $shift1End = Setting::getValue('attendance_shift_1_end', '16:00');
        $shift2Start = Setting::getValue('attendance_shift_2_start', '16:00');
        $shift2End = Setting::getValue('attendance_shift_2_end', '23:00');

        return view('schedules.index', compact(
            'technicians',
            'schedules',
            'year',
            'month',
            'periods',
            'shift1Start',
            'shift1End',
            'shift2Start',
            'shift2End',
            'totalTechnicians',
            'columnsPerPage',
            'columnPage',
            'columnPageCount',
            'columnStart',
            'columnEnd'
        ));
    }

    public function exportPdf(Request $request)
    {
        $this->ensureShiftSettings();
        $this->ensureScheduleUsers();
        [$technicians, $weeks, $year, $month, $shift1Start, $shift1End, $shift2Start, $shift2End] = $this->buildScheduleExportData($request);

        $pdf = Pdf::loadView('schedules.pdf', compact(
            'technicians',
            'weeks',
            'year',
            'month',
            'shift1Start',
            'shift1End',
            'shift2Start',
            'shift2End'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('jadwal-shift-'.sprintf('%04d-%02d', $year, $month).'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $this->ensureShiftSettings();
        $this->ensureScheduleUsers();
        [$technicians, $weeks, $year, $month, $shift1Start, $shift1End, $shift2Start, $shift2End] = $this->buildScheduleExportData($request);

        return response()->streamDownload(function () use ($technicians, $weeks, $shift1Start, $shift1End, $shift2Start, $shift2End) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $header = ['Week', 'Date Range'];
            foreach ($technicians as $tech) {
                $header[] = $tech->schedule_name ?? $tech->name;
            }
            $writer->addRow(Row::fromValues($header));

            foreach ($weeks as $week) {
                $row = [
                    'Week '.$week['week_number'],
                    $week['range'],
                ];

                foreach ($technicians as $tech) {
                    $status = $week['statuses'][$tech->id] ?? 'off';
                    $row[] = match ($status) {
                        'piket' => "Shift 1 ({$shift1Start}-{$shift1End})",
                        'backup' => "Shift 2 ({$shift2Start}-{$shift2End})",
                        default => 'Off',
                    };
                }

                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        }, 'jadwal-shift-'.sprintf('%04d-%02d', $year, $month).'.xlsx');
    }

    public function updatePeriod(Request $request)
    {
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'year' => 'required|integer',
            'week_number' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        SchedulePeriod::updateOrCreate(
            [
                'year' => $request->year,
                'week_number' => $request->week_number,
            ],
            [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]
        );

        return redirect()->back()->with('success', __('Schedule period updated successfully.'));
    }

    public function store(Request $request)
    {
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'week_number' => 'required|integer|min:1|max:53',
            'year' => 'required|integer',
            'status' => 'required|in:piket,off,backup',
            'notes' => 'nullable|string',
        ]);

        TechnicianSchedule::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'week_number' => $request->week_number,
                'year' => $request->year,
            ],
            [
                'status' => $request->status,
                'notes' => $request->notes,
            ]
        );

        return redirect()->back()->with('success', __('Schedule updated successfully.'));
    }

    public function destroy(TechnicianSchedule $schedule)
    {
        $schedule->delete();

        return redirect()->back()->with('success', __('Schedule removed successfully.'));
    }

    private function ensureShiftSettings(): void
    {
        $defaults = [
            ['key' => 'attendance_shift_1_start', 'value' => '08:00', 'label' => 'Jam Mulai Shift 1'],
            ['key' => 'attendance_shift_1_end', 'value' => '16:00', 'label' => 'Jam Selesai Shift 1'],
            ['key' => 'attendance_shift_2_start', 'value' => '16:00', 'label' => 'Jam Mulai Shift 2'],
            ['key' => 'attendance_shift_2_end', 'value' => '23:00', 'label' => 'Jam Selesai Shift 2'],
        ];

        foreach ($defaults as $item) {
            Setting::firstOrCreate(
                ['key' => $item['key']],
                [
                    'value' => $item['value'],
                    'group' => 'attendance',
                    'type' => 'time',
                    'label' => $item['label'],
                ]
            );
        }
    }

    private function scheduleUsersQuery()
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('role', function ($roleQ) {
                $roleQ->whereIn('name', $this->scheduleEligibleRoleNames());
            });
    }

    private function scheduleEligibleRoleNames(): array
    {
        return [
            'admin', // administrasi
            'finance', // administrasi/keuangan
            'noc',
            'network-operations-center',
            'technician',
            'kasir-atk',
            'kasir-wash',
            'karyawan-wash', // operator wash
        ];
    }

    private function scheduleDefaultRoleId(): ?int
    {
        return Role::query()
            ->whereIn('name', ['karyawan-wash', 'technician', 'employee'])
            ->orderByRaw("CASE name WHEN 'karyawan-wash' THEN 1 WHEN 'technician' THEN 2 ELSE 3 END")
            ->value('id');
    }

    private function ensureScheduleUsers(): void
    {
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            return;
        }

        $employeeRoleId = $this->scheduleDefaultRoleId();
        if (! $employeeRoleId) {
            return;
        }

        Employee::query()
            ->whereNull('user_id')
            ->orderBy('id')
            ->each(function (Employee $employee) use ($employeeRoleId) {
                $username = 'emp'.$employee->id;
                if (User::query()->where('username', $username)->exists()) {
                    $username = 'emp'.$employee->id.'_'.strtolower(substr(md5((string) $employee->id), 0, 4));
                }

                $email = $employee->email;
                if ($email && User::query()->where('email', $email)->exists()) {
                    $email = null;
                }
                if (! $email) {
                    $email = $username.'@mstore.local';
                }
                if (User::query()->where('email', $email)->exists()) {
                    $email = $username.'_'.strtolower(substr(md5($username), 0, 4)).'@mstore.local';
                }

                $user = User::create([
                    'name' => $employee->full_name,
                    'username' => $username,
                    'email' => $email,
                    'phone' => $employee->phone,
                    'password' => Hash::make('password'),
                    'role_id' => $employeeRoleId,
                    'is_active' => true,
                ]);

                $employee->user_id = $user->id;
                $employee->save();
            });

        WashEmployee::query()
            ->whereNull('user_id')
            ->orderBy('id')
            ->each(function (WashEmployee $washEmployee) use ($employeeRoleId) {
                $username = 'wash'.$washEmployee->id;
                if (User::query()->where('username', $username)->exists()) {
                    $username = 'wash'.$washEmployee->id.'_'.strtolower(substr(md5((string) $washEmployee->id), 0, 4));
                }

                $user = User::create([
                    'name' => $washEmployee->name,
                    'username' => $username,
                    'email' => User::query()->where('email', $username.'@mstore.local')->exists()
                        ? $username.'_'.strtolower(substr(md5($username), 0, 4)).'@mstore.local'
                        : $username.'@mstore.local',
                    'phone' => $washEmployee->phone,
                    'password' => Hash::make('password'),
                    'role_id' => $employeeRoleId,
                    'is_active' => true,
                ]);

                $washEmployee->user_id = $user->id;
                $washEmployee->save();
            });
    }

    private function buildScheduleExportData(Request $request): array
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $techniciansQuery = $this->scheduleUsersQuery();
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            $techniciansQuery->where('id', Auth::id());
        }
        $technicians = $techniciansQuery->orderBy('name')->get();
        $this->applyScheduleDisplayNames($technicians);
        $technicians = $this->deduplicateScheduleUsers($technicians);

        $schedules = TechnicianSchedule::where('year', $year)->get()->groupBy('week_number');
        $periods = SchedulePeriod::where('year', $year)->get()->keyBy('week_number');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfWeek();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfWeek();
        $weeks = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addWeek()) {
            $weekNum = $date->weekOfYear;
            $period = $periods->get($weekNum);
            $range = ($period ? $period->start_date->translatedFormat('d M') : $date->copy()->startOfWeek()->translatedFormat('d M'))
                .' - '
                .($period ? $period->end_date->translatedFormat('d M') : $date->copy()->endOfWeek()->translatedFormat('d M'));

            $statuses = [];
            foreach ($technicians as $tech) {
                $weekSchedules = $schedules->get($weekNum);
                $schedule = $weekSchedules ? $weekSchedules->where('user_id', $tech->id)->first() : null;
                $statuses[$tech->id] = $schedule?->status ?? 'off';
            }

            $weeks[] = [
                'week_number' => $weekNum,
                'range' => $range,
                'statuses' => $statuses,
            ];
        }

        $shift1Start = Setting::getValue('attendance_shift_1_start', '08:00');
        $shift1End = Setting::getValue('attendance_shift_1_end', '16:00');
        $shift2Start = Setting::getValue('attendance_shift_2_start', '16:00');
        $shift2End = Setting::getValue('attendance_shift_2_end', '23:00');

        return [$technicians, $weeks, $year, $month, $shift1Start, $shift1End, $shift2Start, $shift2End];
    }

    private function applyScheduleDisplayNames($users): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $userIds = $users->pluck('id')->all();
        $employeeNames = Employee::query()
            ->whereIn('user_id', $userIds)
            ->pluck('full_name', 'user_id');
        $washNames = WashEmployee::query()
            ->whereIn('user_id', $userIds)
            ->pluck('name', 'user_id');

        foreach ($users as $user) {
            $fromEmployee = $employeeNames->has($user->id);
            $fromWash = ! $fromEmployee && $washNames->has($user->id);

            $display = $employeeNames->get($user->id)
                ?? $washNames->get($user->id)
                ?? $user->name;
            $user->setAttribute('schedule_name', $display);
            $user->setAttribute('schedule_source_priority', $fromEmployee ? 1 : ($fromWash ? 2 : 3));
        }
    }

    private function deduplicateScheduleUsers($users)
    {
        return $users
            ->sortBy([
                fn ($user) => (int) ($user->schedule_source_priority ?? 99),
                fn ($user) => strtolower((string) ($user->schedule_name ?? $user->name ?? '')),
                fn ($user) => (int) $user->id,
            ])
            ->unique(function ($user) {
                return strtolower(trim((string) ($user->schedule_name ?? $user->name ?? '')));
            })
            ->values();
    }
}
