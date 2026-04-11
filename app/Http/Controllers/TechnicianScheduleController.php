<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\SchedulePeriod;
use App\Models\Setting;
use App\Models\TechnicianDailySchedule;
use App\Models\TechnicianSchedule;
use App\Models\User;
use App\Models\WashEmployee;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Writer\XLSX\Writer;

class TechnicianScheduleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:schedule.view', only: ['index', 'exportPdf', 'exportExcel']),
            new Middleware('permission:schedule.manage', only: ['updatePeriod', 'store', 'autoGenerate', 'dailyStore', 'dailyAutoGenerate', 'importExcel']),
        ];
    }

    public function index(Request $request)
    {
        $this->ensureShiftSettings();
        $this->ensureAutoScheduleSettings();
        $this->ensureDailyScheduleSettings();
        $this->ensureScheduleUsers();
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $mode = (string) $request->input('mode', 'weekly');
        $startDateParam = $request->input('start_date');
        $endDateParam = $request->input('end_date');
        $selectedGroup = (string) $request->input('group', 'all');
        $selectedShift = (string) $request->input('shift', 'all'); // piket|backup|off|all

        if ($mode === 'daily' && ! Schema::hasTable('technician_daily_schedules')) {
            return redirect()
                ->route('schedules.index', ['year' => $year, 'month' => $month, 'mode' => 'weekly'])
                ->with('error', 'Mode Harian membutuhkan migrasi database. Jalankan: php artisan migrate');
        }

        $techniciansQuery = $this->scheduleUsersQuery();

        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            $techniciansQuery->where('id', Auth::id());
        }

        $technicians = $techniciansQuery
            ->orderBy('name')
            ->get();
        $this->applyScheduleDisplayNames($technicians);
        $this->applyScheduleMeta($technicians);
        $technicians = $this->deduplicateScheduleUsers($technicians);

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

        $weeksData = $this->buildWeeksData($year, $month, $periods);

        $groups = [
            [
                'key' => 'teknisi',
                'label' => 'Shift Teknisi',
                'users' => $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === 'teknisi')->values(),
            ],
            [
                'key' => 'wash',
                'label' => 'Shift Operator Wash',
                'users' => $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === 'wash')->values(),
            ],
            [
                'key' => 'lainnya',
                'label' => 'Shift Lainnya',
                'users' => $technicians->filter(fn ($u) => ! in_array(($u->schedule_group ?? ''), ['teknisi', 'wash'], true))->values(),
            ],
        ];

        $shift1Start = Setting::getValue('attendance_shift_1_start', '08:00');
        $shift1End = Setting::getValue('attendance_shift_1_end', '16:00');
        $shift2Start = Setting::getValue('attendance_shift_2_start', '16:00');
        $shift2End = Setting::getValue('attendance_shift_2_end', '23:00');

        $autoShift1Slots = (int) Setting::getValue('schedule_auto_shift1_slots', '1');
        $autoShift2Slots = (int) Setting::getValue('schedule_auto_shift2_slots', '1');
        $dailyOffDays = (int) Setting::getValue('schedule_daily_off_days_per_month', '2');

        $calendarWeeks = [];
        $dailySchedules = collect();
        $dailyRangeStart = null;
        $dailyRangeEnd = null;
        if ($mode === 'daily') {
            $defaultStart = Carbon::createFromDate((int) $year, (int) $month, 1)->startOfDay();
            $defaultEnd = Carbon::createFromDate((int) $year, (int) $month, 1)->endOfMonth()->startOfDay();
            $dailyRangeStart = $defaultStart->copy();
            $dailyRangeEnd = $defaultEnd->copy();

            if (! empty($startDateParam) && ! empty($endDateParam)) {
                try {
                    $s = Carbon::parse($startDateParam)->startOfDay();
                    $e = Carbon::parse($endDateParam)->startOfDay();
                    if ($e->lt($s)) {
                        [$s, $e] = [$e, $s];
                    }
                    $dailyRangeStart = $s;
                    $dailyRangeEnd = $e;
                } catch (\Throwable $e) {
                    // ignore invalid param, fallback to default month
                }
            }

            $calendarWeeks = [];
            $rangeStartForWeeks = $dailyRangeStart->copy()->startOfWeek();
            $rangeEndForWeeks = $dailyRangeEnd->copy()->endOfWeek();
            for ($date = $rangeStartForWeeks->copy(); $date->lte($rangeEndForWeeks); $date->addWeek()) {
                $weekStart = $date->copy()->startOfWeek();
                $daysTmp = [];
                for ($i = 0; $i < 7; $i++) {
                    $daysTmp[] = $weekStart->copy()->addDays($i);
                }
                $calendarWeeks[] = ['start' => $daysTmp[0], 'end' => $daysTmp[6], 'days' => $daysTmp];
            }

            $userIds = $technicians->pluck('id')->values();
            $dailySchedules = TechnicianDailySchedule::query()
                ->whereIn('user_id', $userIds)
                ->whereBetween('date', [$dailyRangeStart->format('Y-m-d'), $dailyRangeEnd->format('Y-m-d')])
                ->get()
                ->groupBy(fn (TechnicianDailySchedule $row) => $row->date->format('Y-m-d'));
        }

        // Apply group filter
        if (in_array($selectedGroup, ['teknisi', 'wash', 'lainnya'], true)) {
            foreach ($groups as &$grp) {
                if (($grp['key'] ?? '') !== $selectedGroup) {
                    $grp['users'] = collect(); // hide
                }
            }
            unset($grp);
        }

        // Apply shift filter
        if (in_array($selectedShift, ['piket', 'backup', 'off'], true)) {
            $allowedIds = collect();
            if ($mode === 'daily') {
                $allowedIds = $dailySchedules
                    ->flatMap(fn ($rows) => $rows)
                    ->filter(fn ($row) => ($row->status ?? 'off') === $selectedShift)
                    ->pluck('user_id')
                    ->unique()
                    ->values();
            } else {
                $weekNumbers = collect($weeksData)->pluck('week_number')->values();
                $ids = collect();
                foreach ($weekNumbers as $w) {
                    $weekRows = $schedules->get($w);
                    if ($weekRows) {
                        $ids = $ids->merge($weekRows->where('status', $selectedShift)->pluck('user_id'));
                    }
                }
                $allowedIds = $ids->unique()->values();
            }

            foreach ($groups as &$grp) {
                $grp['users'] = ($grp['users'] ?? collect())->filter(fn ($u) => $allowedIds->contains($u->id))->values();
            }
            unset($grp);
        }

        return view('schedules.index', compact(
            'technicians',
            'groups',
            'schedules',
            'year',
            'month',
            'mode',
            'periods',
            'weeksData',
            'calendarWeeks',
            'dailySchedules',
            'dailyRangeStart',
            'dailyRangeEnd',
            'selectedGroup',
            'selectedShift',
            'shift1Start',
            'shift1End',
            'shift2Start',
            'shift2End',
            'autoShift1Slots',
            'autoShift2Slots',
            'dailyOffDays'
        ));
    }

    public function autoGenerate(Request $request)
    {
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'shift1_slots' => ['nullable', 'integer', 'min:1', 'max:50'],
            'shift2_slots' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        $shift1Slots = (int) ($request->input('shift1_slots') ?? Setting::getValue('schedule_auto_shift1_slots', '1'));
        $shift2Slots = (int) ($request->input('shift2_slots') ?? Setting::getValue('schedule_auto_shift2_slots', '1'));
        if ($shift1Slots < 1) {
            $shift1Slots = 1;
        }
        if ($shift2Slots < 1) {
            $shift2Slots = 1;
        }

        Setting::updateOrCreate(
            ['key' => 'schedule_auto_shift1_slots'],
            ['value' => (string) $shift1Slots, 'group' => 'schedule', 'type' => 'number', 'label' => 'Auto Schedule Slot Shift 1 per Minggu']
        );
        Setting::updateOrCreate(
            ['key' => 'schedule_auto_shift2_slots'],
            ['value' => (string) $shift2Slots, 'group' => 'schedule', 'type' => 'number', 'label' => 'Auto Schedule Slot Shift 2 per Minggu']
        );

        $technicians = $this->scheduleUsersQuery()
            ->orderBy('name')
            ->get();
        $this->applyScheduleDisplayNames($technicians);
        $this->applyScheduleMeta($technicians);
        $technicians = $this->deduplicateScheduleUsers($technicians);

        $periods = SchedulePeriod::where('year', $year)->get()->keyBy('week_number');
        $weeksData = $this->buildWeeksData($year, $month, $periods);
        $weekNumbers = collect($weeksData)->pluck('week_number')->values();

        $groups = [
            $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === 'teknisi')->values(),
            $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === 'wash')->values(),
            $technicians->filter(fn ($u) => ! in_array(($u->schedule_group ?? ''), ['teknisi', 'wash'], true))->values(),
        ];

        DB::transaction(function () use ($groups, $year, $weekNumbers, $shift1Slots, $shift2Slots) {
            foreach ($groups as $users) {
                if ($users->isEmpty()) {
                    continue;
                }

                $userIds = $users->pluck('id')->values();
                $counts = [];
                foreach ($userIds as $id) {
                    $counts[$id] = ['s1' => 0, 's2' => 0, 'assign' => 0];
                }

                $rotation = $userIds->values();
                $lastShift = [];
                $userIndex = [];
                foreach ($rotation as $idx => $id) {
                    $userIndex[$id] = (int) $idx;
                }

                foreach ($weekNumbers as $wIndex => $weekNumber) {
                    $selectedS1 = collect();
                    $selectedS2 = collect();

                    $desiredShiftForWeek = function (int $userId, int $weekIndex) use ($userIndex): string {
                        $idx = $userIndex[$userId] ?? 0;

                        return ((($idx + $weekIndex) % 2) === 0) ? 'piket' : 'backup';
                    };

                    $pickCandidates = function (string $preferredShift) use (&$counts, $rotation, &$lastShift, $desiredShiftForWeek, $wIndex) {
                        return $rotation->sortBy(function ($id) use (&$counts, &$lastShift, $preferredShift, $desiredShiftForWeek, $wIndex) {
                            $repeatPenalty = (($lastShift[$id] ?? null) === $preferredShift) ? 1 : 0;
                            $desiredPenalty = ($desiredShiftForWeek((int) $id, (int) $wIndex) === $preferredShift) ? 0 : 1;

                            return ($counts[$id]['assign'] * 10000)
                                + ($desiredPenalty * 1000)
                                + ($repeatPenalty * 100)
                                + $counts[$id]['s1']
                                + $counts[$id]['s2'];
                        })->values();
                    };

                    foreach ($pickCandidates('piket') as $id) {
                        if ($selectedS1->count() >= $shift1Slots) {
                            break;
                        }
                        $selectedS1->push($id);
                        $counts[$id]['s1']++;
                        $counts[$id]['assign']++;
                    }

                    foreach ($pickCandidates('backup') as $id) {
                        if ($selectedS2->count() >= $shift2Slots) {
                            break;
                        }
                        if ($selectedS1->contains($id)) {
                            continue;
                        }
                        $selectedS2->push($id);
                        $counts[$id]['s2']++;
                        $counts[$id]['assign']++;
                    }

                    foreach ($userIds as $id) {
                        $status = 'off';
                        if ($selectedS1->contains($id)) {
                            $status = 'piket';
                        } elseif ($selectedS2->contains($id)) {
                            $status = 'backup';
                        }

                        if ($status !== 'off') {
                            $lastShift[$id] = $status;
                        }

                        TechnicianSchedule::updateOrCreate(
                            [
                                'user_id' => $id,
                                'week_number' => $weekNumber,
                                'year' => $year,
                            ],
                            [
                                'status' => $status,
                                'notes' => null,
                            ]
                        );
                    }
                }
            }
        });

        return redirect()->route('schedules.index', ['year' => $year, 'month' => $month])->with('success', 'Auto schedule berhasil dibuat untuk bulan ini.');
    }

    public function dailyAutoGenerate(Request $request)
    {
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        if (! Schema::hasTable('technician_daily_schedules')) {
            return redirect()->back()->with('error', 'Tabel jadwal harian belum ada. Jalankan: php artisan migrate');
        }

        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'off_days' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $offDays = (int) $request->input('off_days', 2);

        $daysInMonth = (int) Carbon::createFromDate($year, $month, 1)->daysInMonth;
        if ($offDays >= $daysInMonth) {
            return redirect()->back()->with('error', 'Jumlah libur terlalu besar untuk bulan ini.');
        }

        Setting::updateOrCreate(
            ['key' => 'schedule_daily_off_days_per_month'],
            ['value' => (string) $offDays, 'group' => 'schedule', 'type' => 'number', 'label' => 'Libur per Bulan (Harian)']
        );

        $technicians = $this->scheduleUsersQuery()
            ->orderBy('name')
            ->get();
        $this->applyScheduleDisplayNames($technicians);
        $this->applyScheduleMeta($technicians);
        $technicians = $this->deduplicateScheduleUsers($technicians);

        $groups = [
            $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === 'teknisi')->values(),
            $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === 'wash')->values(),
            $technicians->filter(fn ($u) => ! in_array(($u->schedule_group ?? ''), ['teknisi', 'wash'], true))->values(),
        ];

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->startOfDay();

        DB::transaction(function () use ($groups, $start, $end, $offDays) {
            foreach ($groups as $users) {
                if ($users->isEmpty()) {
                    continue;
                }

                $userIds = $users->pluck('id')->values();
                TechnicianDailySchedule::query()
                    ->whereIn('user_id', $userIds)
                    ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->delete();

                $plan = $this->generateDailyPlan($userIds->all(), $start->copy(), $end->copy(), $offDays);
                $now = now();
                $rows = [];
                foreach ($plan as $date => $statusesByUserId) {
                    foreach ($statusesByUserId as $userId => $status) {
                        $rows[] = [
                            'user_id' => (int) $userId,
                            'date' => $date,
                            'status' => $status,
                            'notes' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                DB::table('technician_daily_schedules')->upsert(
                    $rows,
                    ['user_id', 'date'],
                    ['status', 'notes', 'updated_at']
                );
            }
        });

        return redirect()->route('schedules.index', ['year' => $year, 'month' => $month, 'mode' => 'daily'])
            ->with('success', 'Auto schedule harian berhasil dibuat (libur rata per orang).');
    }

    public function exportPdf(Request $request)
    {
        $this->ensureShiftSettings();
        $this->ensureAutoScheduleSettings();
        $this->ensureDailyScheduleSettings();
        $this->ensureScheduleUsers();
        $mode = (string) $request->input('mode', 'weekly');

        if ($mode === 'daily') {
            if (! Schema::hasTable('technician_daily_schedules')) {
                return redirect()
                    ->route('schedules.index', ['year' => $request->input('year', now()->year), 'month' => $request->input('month', now()->month), 'mode' => 'weekly'])
                    ->with('error', 'Mode Harian membutuhkan migrasi database. Jalankan: php artisan migrate');
            }

            $year = (int) $request->input('year', now()->year);
            $month = (int) $request->input('month', now()->month);
            $startDateParam = $request->input('start_date');
            $endDateParam = $request->input('end_date');

            $techniciansQuery = $this->scheduleUsersQuery();
            if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
                $techniciansQuery->where('id', Auth::id());
            }

            $technicians = $techniciansQuery->orderBy('name')->get();
            $this->applyScheduleDisplayNames($technicians);
            $this->applyScheduleMeta($technicians);
            $technicians = $this->deduplicateScheduleUsers($technicians);

            $groups = [
                [
                    'key' => 'teknisi',
                    'label' => 'Shift Teknisi',
                    'users' => $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === 'teknisi')->values(),
                ],
                [
                    'key' => 'wash',
                    'label' => 'Shift Operator Wash',
                    'users' => $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === 'wash')->values(),
                ],
                [
                    'key' => 'lainnya',
                    'label' => 'Shift Lainnya',
                    'users' => $technicians->filter(fn ($u) => ! in_array(($u->schedule_group ?? ''), ['teknisi', 'wash'], true))->values(),
                ],
            ];

            $selectedGroup = (string) $request->input('group', 'all');
            $selectedShift = (string) $request->input('shift', 'all');

            $defaultStart = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $defaultEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->startOfDay();
            $rangeStart = $defaultStart->copy();
            $rangeEnd = $defaultEnd->copy();
            if (! empty($startDateParam) && ! empty($endDateParam)) {
                try {
                    $s = Carbon::parse($startDateParam)->startOfDay();
                    $e = Carbon::parse($endDateParam)->startOfDay();
                    if ($e->lt($s)) {
                        [$s, $e] = [$e, $s];
                    }
                    $rangeStart = $s;
                    $rangeEnd = $e;
                } catch (\Throwable $e) {
                    // fallback ke default
                }
            }

            $calendarWeeks = [];
            $rangeStartForWeeks = $rangeStart->copy()->startOfWeek();
            $rangeEndForWeeks = $rangeEnd->copy()->endOfWeek();
            for ($date = $rangeStartForWeeks->copy(); $date->lte($rangeEndForWeeks); $date->addWeek()) {
                $weekStart = $date->copy()->startOfWeek();
                $daysTmp = [];
                for ($i = 0; $i < 7; $i++) {
                    $daysTmp[] = $weekStart->copy()->addDays($i);
                }
                $calendarWeeks[] = ['start' => $daysTmp[0], 'end' => $daysTmp[6], 'days' => $daysTmp];
            }
            $userIds = $technicians->pluck('id')->values();

            $dailySchedules = TechnicianDailySchedule::query()
                ->whereIn('user_id', $userIds)
                ->whereBetween('date', [$rangeStart->format('Y-m-d'), $rangeEnd->format('Y-m-d')])
                ->get()
                ->groupBy(fn (TechnicianDailySchedule $row) => $row->date->format('Y-m-d'));

            if (in_array($selectedGroup, ['teknisi', 'wash', 'lainnya'], true)) {
                foreach ($groups as &$grp) {
                    if (($grp['key'] ?? '') !== $selectedGroup) {
                        $grp['users'] = collect();
                    }
                }
                unset($grp);
            }
            if (in_array($selectedShift, ['piket', 'backup', 'off'], true)) {
                $allowedIds = $dailySchedules
                    ->flatMap(fn ($rows) => $rows)
                    ->filter(fn ($row) => ($row->status ?? 'off') === $selectedShift)
                    ->pluck('user_id')
                    ->unique()
                    ->values();
                foreach ($groups as &$grp) {
                    $grp['users'] = ($grp['users'] ?? collect())->filter(fn ($u) => $allowedIds->contains($u->id))->values();
                }
                unset($grp);
            }

            $shift1Start = Setting::getValue('attendance_shift_1_start', '08:00');
            $shift1End = Setting::getValue('attendance_shift_1_end', '16:00');
            $shift2Start = Setting::getValue('attendance_shift_2_start', '16:00');
            $shift2End = Setting::getValue('attendance_shift_2_end', '23:00');

            $pdf = Pdf::loadView('schedules.pdf', compact(
                'mode',
                'technicians',
                'groups',
                'calendarWeeks',
                'dailySchedules',
                'year',
                'month',
                'shift1Start',
                'shift1End',
                'shift2Start',
                'shift2End'
            ))->setPaper('a4', 'landscape');

            return $pdf->download('jadwal-harian-'.sprintf('%04d-%02d', $year, $month).'.pdf');
        }

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $techniciansQuery = $this->scheduleUsersQuery();
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            $techniciansQuery->where('id', Auth::id());
        }

        $technicians = $techniciansQuery->orderBy('name')->get();
        $this->applyScheduleDisplayNames($technicians);
        $this->applyScheduleMeta($technicians);
        $technicians = $this->deduplicateScheduleUsers($technicians);

        $schedules = TechnicianSchedule::where('year', $year)->get()->groupBy('week_number');
        $periods = SchedulePeriod::where('year', $year)->get()->keyBy('week_number');
        $weeksData = $this->buildWeeksData($year, $month, $periods);

        $groups = [
            [
                'key' => 'teknisi',
                'label' => 'Shift Teknisi',
                'users' => $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === 'teknisi')->values(),
            ],
            [
                'key' => 'wash',
                'label' => 'Shift Operator Wash',
                'users' => $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === 'wash')->values(),
            ],
            [
                'key' => 'lainnya',
                'label' => 'Shift Lainnya',
                'users' => $technicians->filter(fn ($u) => ! in_array(($u->schedule_group ?? ''), ['teknisi', 'wash'], true))->values(),
            ],
        ];

        $shift1Start = Setting::getValue('attendance_shift_1_start', '08:00');
        $shift1End = Setting::getValue('attendance_shift_1_end', '16:00');
        $shift2Start = Setting::getValue('attendance_shift_2_start', '16:00');
        $shift2End = Setting::getValue('attendance_shift_2_end', '23:00');

        $pdf = Pdf::loadView('schedules.pdf', compact(
            'mode',
            'technicians',
            'groups',
            'weeksData',
            'schedules',
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
        $this->ensureAutoScheduleSettings();
        $this->ensureDailyScheduleSettings();

        $mode = (string) $request->input('mode', 'weekly');
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $techniciansQuery = $this->scheduleUsersQuery();
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            $techniciansQuery->where('id', Auth::id());
        }
        $technicians = $techniciansQuery->orderBy('name')->get();
        $this->applyScheduleDisplayNames($technicians);
        $this->applyScheduleMeta($technicians);
        $technicians = $this->deduplicateScheduleUsers($technicians);

        $shift1Start = Setting::getValue('attendance_shift_1_start', '08:00');
        $shift1End = Setting::getValue('attendance_shift_1_end', '16:00');
        $shift2Start = Setting::getValue('attendance_shift_2_start', '16:00');
        $shift2End = Setting::getValue('attendance_shift_2_end', '23:00');

        if ($mode === 'daily') {
            if (! Schema::hasTable('technician_daily_schedules')) {
                return redirect()->back()->with('error', 'Mode Harian membutuhkan migrasi database. Jalankan: php artisan migrate');
            }

            $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->startOfDay();
            $startDateParam = $request->input('start_date');
            $endDateParam = $request->input('end_date');
            if (! empty($startDateParam) && ! empty($endDateParam)) {
                try {
                    $s = Carbon::parse($startDateParam)->startOfDay();
                    $e = Carbon::parse($endDateParam)->startOfDay();
                    if ($e->lt($s)) {
                        [$s, $e] = [$e, $s];
                    }
                    $start = $s;
                    $end = $e;
                } catch (\Throwable $e) {
                }
            }
            $dates = [];
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dates[] = $d->copy();
            }

            $userIds = $technicians->pluck('id')->values();
            $rows = TechnicianDailySchedule::query()
                ->whereIn('user_id', $userIds)
                ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->get()
                ->groupBy(fn (TechnicianDailySchedule $row) => $row->date->format('Y-m-d'));

            return response()->streamDownload(function () use ($technicians, $dates, $rows) {
                $writer = new Writer;
                $writer->openToFile('php://output');

                $header = ['user_id', 'nama', 'group', 'department', 'position'];
                foreach ($dates as $date) {
                    $header[] = $date->format('Y-m-d');
                }
                $writer->addRow(Row::fromValues($header));

                foreach ($technicians as $tech) {
                    $row = [
                        $tech->id,
                        $tech->schedule_name ?? $tech->name,
                        $tech->schedule_group ?? '',
                        $tech->schedule_department ?? '',
                        $tech->schedule_position ?? '',
                    ];

                    foreach ($dates as $date) {
                        $key = $date->format('Y-m-d');
                        $dayRows = $rows->get($key);
                        $r = $dayRows ? $dayRows->firstWhere('user_id', $tech->id) : null;
                        $status = $r?->status ?? 'off';
                        $row[] = match ($status) {
                            'piket' => 'S1',
                            'backup' => 'S2',
                            default => 'OFF',
                        };
                    }

                    $writer->addRow(Row::fromValues($row));
                }

                $writer->close();
            }, 'jadwal-harian-'.sprintf('%04d-%02d', $year, $month).'.xlsx');
        }

        $periods = SchedulePeriod::where('year', $year)->get()->keyBy('week_number');
        $weeksData = $this->buildWeeksData($year, $month, $periods);
        $weekNumbers = collect($weeksData)->pluck('week_number')->values();
        $schedules = TechnicianSchedule::where('year', $year)->get()->groupBy('week_number');

        return response()->streamDownload(function () use ($technicians, $weekNumbers, $schedules, $shift1Start, $shift1End, $shift2Start, $shift2End) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $header = ['user_id', 'nama', 'group', 'department', 'position'];
            foreach ($weekNumbers as $weekNumber) {
                $header[] = 'W'.$weekNumber;
            }
            $writer->addRow(Row::fromValues($header));

            foreach ($technicians as $tech) {
                $row = [
                    $tech->id,
                    $tech->schedule_name ?? $tech->name,
                    $tech->schedule_group ?? '',
                    $tech->schedule_department ?? '',
                    $tech->schedule_position ?? '',
                ];

                foreach ($weekNumbers as $weekNumber) {
                    $weekRows = $schedules->get($weekNumber);
                    $schedule = $weekRows ? $weekRows->firstWhere('user_id', $tech->id) : null;
                    $status = $schedule?->status ?? 'off';
                    $row[] = match ($status) {
                        'piket' => "S1 ({$shift1Start}-{$shift1End})",
                        'backup' => "S2 ({$shift2Start}-{$shift2End})",
                        default => 'OFF',
                    };
                }

                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        }, 'jadwal-shift-'.sprintf('%04d-%02d', $year, $month).'.xlsx');
    }

    public function importExcel(Request $request)
    {
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'mode' => ['required', 'in:weekly,daily'],
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $mode = (string) $request->input('mode');

        if ($mode === 'daily' && ! Schema::hasTable('technician_daily_schedules')) {
            return redirect()->back()->with('error', 'Mode Harian membutuhkan migrasi database. Jalankan: php artisan migrate');
        }

        $reader = new XLSXReader;
        $reader->open($request->file('file')->getRealPath());

        $header = null;
        $imported = 0;
        $skipped = 0;

        $statusFromCell = function ($value): string {
            $v = strtoupper(trim((string) $value));
            if ($v === '' || $v === '-' || $v === '0' || $v === 'OFF') {
                return 'off';
            }
            if (in_array($v, ['S1', 'SHIFT1', 'SHIFT 1', '1', 'PIKET'], true)) {
                return 'piket';
            }
            if (in_array($v, ['S2', 'SHIFT2', 'SHIFT 2', '2', 'BACKUP'], true)) {
                return 'backup';
            }
            if (str_contains($v, 'S1')) {
                return 'piket';
            }
            if (str_contains($v, 'S2')) {
                return 'backup';
            }

            return 'off';
        };

        try {
            DB::transaction(function () use ($reader, &$header, $mode, $year, $month, &$imported, &$skipped, $statusFromCell) {
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $values = array_map(fn ($c) => $c->getValue(), $row->getCells());

                        if ($header === null) {
                            $header = array_map(fn ($v) => strtolower(trim((string) $v)), $values);

                            continue;
                        }

                        if (count(array_filter($values, fn ($v) => trim((string) $v) !== '')) === 0) {
                            continue;
                        }

                        $values = array_pad(array_slice($values, 0, count($header)), count($header), null);
                        $rowMap = array_combine($header, $values);
                        $userId = (int) ($rowMap['user_id'] ?? 0);
                        if ($userId < 1 || ! User::query()->whereKey($userId)->exists()) {
                            $skipped++;

                            continue;
                        }

                        if ($mode === 'daily') {
                            foreach ($rowMap as $key => $val) {
                                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $key)) {
                                    continue;
                                }
                                $date = Carbon::parse($key);
                                if ($date->year !== $year || $date->month !== $month) {
                                    continue;
                                }
                                TechnicianDailySchedule::updateOrCreate(
                                    ['user_id' => $userId, 'date' => $date->format('Y-m-d')],
                                    ['status' => $statusFromCell($val), 'notes' => null]
                                );
                                $imported++;
                            }
                        } else {
                            foreach ($rowMap as $key => $val) {
                                $k = strtoupper(trim((string) $key));
                                if (! preg_match('/^W(\d{1,2})$/', $k, $m)) {
                                    continue;
                                }
                                $weekNumber = (int) $m[1];
                                TechnicianSchedule::updateOrCreate(
                                    ['user_id' => $userId, 'week_number' => $weekNumber, 'year' => $year],
                                    ['status' => $statusFromCell($val), 'notes' => null]
                                );
                                $imported++;
                            }
                        }
                    }
                }
            });
        } finally {
            $reader->close();
        }

        return redirect()->route('schedules.index', ['year' => $year, 'month' => $month, 'mode' => $mode])
            ->with('success', "Import Excel selesai. Imported: {$imported}, Skipped: {$skipped}");
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

    public function dailyStore(Request $request)
    {
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        if (! Schema::hasTable('technician_daily_schedules')) {
            return redirect()->back()->with('error', 'Tabel jadwal harian belum ada. Jalankan: php artisan migrate');
        }

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:piket,off,backup'],
        ]);

        TechnicianDailySchedule::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'date' => $request->date,
            ],
            [
                'status' => $request->status,
                'notes' => null,
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

    private function ensureAutoScheduleSettings(): void
    {
        $defaults = [
            ['key' => 'schedule_auto_shift1_slots', 'value' => '1', 'label' => 'Auto Schedule Slot Shift 1 per Minggu'],
            ['key' => 'schedule_auto_shift2_slots', 'value' => '1', 'label' => 'Auto Schedule Slot Shift 2 per Minggu'],
        ];

        foreach ($defaults as $item) {
            Setting::firstOrCreate(
                ['key' => $item['key']],
                [
                    'value' => $item['value'],
                    'group' => 'schedule',
                    'type' => 'number',
                    'label' => $item['label'],
                ]
            );
        }
    }

    private function ensureDailyScheduleSettings(): void
    {
        Setting::firstOrCreate(
            ['key' => 'schedule_daily_off_days_per_month'],
            [
                'value' => '2',
                'group' => 'schedule',
                'type' => 'number',
                'label' => 'Libur per Bulan (Harian)',
            ]
        );
    }

    private function buildCalendarWeeks(int $year, int $month): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfWeek();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfWeek();

        $weeks = [];
        for ($date = $start->copy(); $date->lte($end); $date->addWeek()) {
            $weekStart = $date->copy()->startOfWeek();
            $days = [];
            for ($i = 0; $i < 7; $i++) {
                $days[] = $weekStart->copy()->addDays($i);
            }
            $weeks[] = [
                'start' => $days[0],
                'end' => $days[6],
                'days' => $days,
            ];
        }

        return $weeks;
    }

    private function generateDailyPlan(array $userIds, Carbon $start, Carbon $end, int $offDaysPerUser): array
    {
        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = $d->copy();
        }

        $userIds = array_values($userIds);
        $n = count($userIds);
        if ($n === 0) {
            return [];
        }

        $remainingOff = [];
        $lastOff = [];
        foreach ($userIds as $id) {
            $remainingOff[$id] = $offDaysPerUser;
            $lastOff[$id] = null;
        }

        $totalOffSlots = $n * $offDaysPerUser;
        $daysCount = count($days);
        $baseOffPerDay = intdiv($totalOffSlots, $daysCount);
        $extraOffDays = $totalOffSlots % $daysCount;

        $weekStart = $start->copy()->startOfWeek();
        $weekIndexByDate = [];
        foreach ($days as $day) {
            $weekIndexByDate[$day->format('Y-m-d')] = (int) floor($weekStart->diffInDays($day->copy()->startOfWeek()) / 7);
        }

        $userIndex = [];
        foreach ($userIds as $idx => $id) {
            $userIndex[$id] = $idx;
        }

        $plan = [];
        foreach ($days as $i => $day) {
            $dateKey = $day->format('Y-m-d');
            $offCountToday = $baseOffPerDay + ($i < $extraOffDays ? 1 : 0);
            if ($offCountToday > $n) {
                $offCountToday = $n;
            }

            $candidates = array_values(array_filter($userIds, fn ($id) => $remainingOff[$id] > 0));
            usort($candidates, function ($a, $b) use ($remainingOff, $lastOff, $day) {
                $yesterday = $day->copy()->subDay()->format('Y-m-d');
                $aConsecutive = $lastOff[$a] === $yesterday ? 1 : 0;
                $bConsecutive = $lastOff[$b] === $yesterday ? 1 : 0;
                if ($aConsecutive !== $bConsecutive) {
                    return $aConsecutive <=> $bConsecutive;
                }
                if ($remainingOff[$a] !== $remainingOff[$b]) {
                    return $remainingOff[$b] <=> $remainingOff[$a];
                }

                return $a <=> $b;
            });

            $offUsers = array_slice($candidates, 0, $offCountToday);
            $offSet = array_flip($offUsers);

            foreach ($offUsers as $id) {
                $remainingOff[$id]--;
                $lastOff[$id] = $dateKey;
            }

            $weekIndex = $weekIndexByDate[$dateKey] ?? 0;

            $statuses = [];
            foreach ($userIds as $id) {
                if (isset($offSet[$id])) {
                    $statuses[$id] = 'off';

                    continue;
                }

                $idx = $userIndex[$id] ?? 0;
                $statuses[$id] = ((($idx + $weekIndex) % 2) === 0) ? 'piket' : 'backup';
            }

            $plan[$dateKey] = $statuses;
        }

        return $plan;
    }

    private function scheduleUsersQuery()
    {
        $employeeUserIds = Employee::query()
            ->whereNotNull('user_id')
            ->pluck('user_id');
        $washUserIds = WashEmployee::query()
            ->whereNotNull('user_id')
            ->pluck('user_id');
        $ids = $employeeUserIds->merge($washUserIds)->unique()->values();

        return User::query()
            ->where('is_active', true)
            ->whereHas('role', function ($roleQ) {
                $roleQ->whereNotIn('name', $this->scheduleExcludedRoleNames());
            })
            ->where(function ($q) use ($ids) {
                $q->whereIn('id', $ids);

                $included = $this->scheduleIncludedRoleNames();
                if (! empty($included)) {
                    $q->orWhereHas('role', function ($roleQ) use ($included) {
                        $roleQ->whereIn('name', $included);
                    });
                }
            });
    }

    private function scheduleIncludedRoleNames(): array
    {
        return [
            'technician',
            'noc',
            'network-operations-center',
            'finance',
            'kasir-atk',
            'kasir-wash',
            'karyawan-wash',
        ];
    }

    private function scheduleExcludedRoleNames(): array
    {
        return [
            'admin',
            'coordinator',
            'owner',
            'owner-pendiri',
            'customer',
            'reseller',
        ];
    }

    private function scheduleRoleIds(): array
    {
        return Role::query()
            ->whereIn('name', [
                'technician',
                'karyawan-wash',
                'kasir-wash',
                'kasir-atk',
                'finance',
                'noc',
                'network-operations-center',
            ])
            ->pluck('id', 'name')
            ->toArray();
    }

    private function ensureScheduleUsers(): void
    {
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            return;
        }

        $roleIds = $this->scheduleRoleIds();
        $fallbackRoleId = $roleIds['technician'] ?? $roleIds['noc'] ?? $roleIds['network-operations-center'] ?? null;
        if (! $fallbackRoleId) {
            return;
        }

        Employee::query()
            ->whereNull('user_id')
            ->orderBy('id')
            ->each(function (Employee $employee) use ($roleIds, $fallbackRoleId) {
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

                $department = strtolower(trim((string) $employee->department));
                $position = strtolower(trim((string) $employee->position));

                $roleId = $fallbackRoleId;
                if ($department === 'wash') {
                    $roleId = str_contains($position, 'kasir')
                        ? ($roleIds['kasir-wash'] ?? $roleIds['karyawan-wash'] ?? $fallbackRoleId)
                        : ($roleIds['karyawan-wash'] ?? $fallbackRoleId);
                } elseif ($department === 'atk') {
                    $roleId = $roleIds['kasir-atk'] ?? $fallbackRoleId;
                } elseif ($department === 'keuangan') {
                    $roleId = $roleIds['finance'] ?? $fallbackRoleId;
                } elseif ($department === 'teknis' || str_contains($position, 'teknisi') || str_contains($position, 'technician')) {
                    $roleId = $roleIds['technician'] ?? $fallbackRoleId;
                }

                $user = User::create([
                    'name' => $employee->full_name,
                    'username' => $username,
                    'email' => $email,
                    'phone' => $employee->phone,
                    'password' => Hash::make('password'),
                    'role_id' => $roleId,
                    'is_active' => true,
                ]);

                $employee->user_id = $user->id;
                $employee->save();
            });

        WashEmployee::query()
            ->whereNull('user_id')
            ->orderBy('id')
            ->each(function (WashEmployee $washEmployee) use ($roleIds, $fallbackRoleId) {
                $username = 'wash'.$washEmployee->id;
                if (User::query()->where('username', $username)->exists()) {
                    $username = 'wash'.$washEmployee->id.'_'.strtolower(substr(md5((string) $washEmployee->id), 0, 4));
                }

                $roleId = $roleIds['karyawan-wash'] ?? $fallbackRoleId;

                $user = User::create([
                    'name' => $washEmployee->name,
                    'username' => $username,
                    'email' => User::query()->where('email', $username.'@mstore.local')->exists()
                        ? $username.'_'.strtolower(substr(md5($username), 0, 4)).'@mstore.local'
                        : $username.'@mstore.local',
                    'phone' => $washEmployee->phone,
                    'password' => Hash::make('password'),
                    'role_id' => $roleId,
                    'is_active' => true,
                ]);

                $washEmployee->user_id = $user->id;
                $washEmployee->save();
            });
    }

    private function buildWeeksData(int $year, int $month, $periods): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfWeek();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfWeek();
        $weeksData = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addWeek()) {
            $weekNum = $date->weekOfYear;
            $period = $periods->get($weekNum);
            $currentWeekStart = $period ? $period->start_date : $date->copy()->startOfWeek();
            $currentWeekEnd = $period ? $period->end_date : $date->copy()->endOfWeek();
            $weeksData[] = [
                'week_number' => $weekNum,
                'week_start_display' => $currentWeekStart->translatedFormat('d M'),
                'week_end_display' => $currentWeekEnd->translatedFormat('d M'),
                'full_start_date' => $currentWeekStart->format('Y-m-d'),
                'full_end_date' => $currentWeekEnd->format('Y-m-d'),
            ];
        }

        return $weeksData;
    }

    private function applyScheduleMeta($users): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $userIds = $users->pluck('id')->all();
        $employees = Employee::query()
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'department', 'position']);
        $washUserIds = WashEmployee::query()
            ->whereIn('user_id', $userIds)
            ->pluck('user_id')
            ->flip();

        $employeeByUserId = $employees->keyBy('user_id');

        foreach ($users as $user) {
            $department = (string) ($employeeByUserId->get($user->id)?->department ?? '');
            $position = (string) ($employeeByUserId->get($user->id)?->position ?? '');

            if ($washUserIds->has($user->id)) {
                $department = 'Wash';
                if ($position === '' || strtolower($position) === 'karyawan wash') {
                    $position = 'Operator Wash';
                }
            }

            if ($position === '') {
                $position = $user->role?->label ?? $user->role?->name ?? '';
            }
            if ($department === '') {
                $roleName = strtolower((string) ($user->role?->name ?? ''));
                $department = match ($roleName) {
                    'technician', 'noc', 'network-operations-center' => 'Teknis',
                    'finance' => 'Keuangan',
                    'kasir-atk' => 'ATK',
                    'kasir-wash', 'karyawan-wash' => 'Wash',
                    default => 'Operasional',
                };
            }

            $group = 'lainnya';
            $deptLower = strtolower($department);
            $roleLower = strtolower((string) ($user->role?->name ?? ''));
            if ($deptLower === 'wash' || in_array($roleLower, ['kasir-wash', 'karyawan-wash'], true)) {
                $group = 'wash';
            } elseif ($deptLower === 'teknis' || in_array($roleLower, ['technician', 'noc', 'network-operations-center'], true)) {
                $group = 'teknisi';
            }

            $user->setAttribute('schedule_department', $department);
            $user->setAttribute('schedule_position', $position);
            $user->setAttribute('schedule_group', $group);
        }
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
