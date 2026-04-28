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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
        Gate::authorize('schedule.view');

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
        $selectedShift = (string) $request->input('shift', 'all'); // piket|backup|longshift|off|all

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
            ->groupBy('week_number')
            ->map(function ($items) {
                return $items->keyBy('user_id');
            });

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

        $shiftConfig = $this->getShiftConfig();

        $autoShift1Slots = (int) Setting::getValue('schedule_auto_shift1_slots', '1');
        $autoShift2Slots = (int) Setting::getValue('schedule_auto_shift2_slots', '1');
        $autoLongshiftSlots = (int) Setting::getValue('schedule_auto_longshift_slots', '0');
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
                ->groupBy(fn (TechnicianDailySchedule $row) => $row->date->format('Y-m-d'))
                ->map(function ($items) {
                    return $items->keyBy('user_id');
                });
            $dailySchedules = $this->mergeDailyWithWeeklyFallback(
                $dailySchedules,
                $userIds->all(),
                $dailyRangeStart,
                $dailyRangeEnd
            );
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
        if (in_array($selectedShift, ['piket', 'backup', 'longshift', 'off'], true)) {
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
            'shiftConfig',
            'autoShift1Slots',
            'autoShift2Slots',
            'autoLongshiftSlots',
            'dailyOffDays'
        ));
    }

    public function autoGenerate(Request $request)
    {
        Gate::authorize('schedule.manage');

        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'shift1_slots' => ['nullable', 'integer', 'min:1', 'max:50'],
            'shift2_slots' => ['nullable', 'integer', 'min:1', 'max:50'],
            'longshift_slots' => ['nullable', 'integer', 'min:0', 'max:50'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $selectedUserIds = $request->input('user_ids');

        $shift1Slots = (int) ($request->input('shift1_slots') ?? Setting::getValue('schedule_auto_shift1_slots', '1'));
        $shift2Slots = (int) ($request->input('shift2_slots') ?? Setting::getValue('schedule_auto_shift2_slots', '1'));
        $longshiftSlots = (int) ($request->input('longshift_slots') ?? Setting::getValue('schedule_auto_longshift_slots', '0'));
        if ($shift1Slots < 1) {
            $shift1Slots = 1;
        }
        if ($shift2Slots < 1) {
            $shift2Slots = 1;
        }
        if ($longshiftSlots < 0) {
            $longshiftSlots = 0;
        }

        Setting::updateOrCreate(
            ['key' => 'schedule_auto_shift1_slots'],
            ['value' => (string) $shift1Slots, 'group' => 'schedule', 'type' => 'number', 'label' => 'Auto Schedule Slot Shift 1 per Minggu']
        );
        Setting::updateOrCreate(
            ['key' => 'schedule_auto_shift2_slots'],
            ['value' => (string) $shift2Slots, 'group' => 'schedule', 'type' => 'number', 'label' => 'Auto Schedule Slot Shift 2 per Minggu']
        );
        Setting::updateOrCreate(
            ['key' => 'schedule_auto_longshift_slots'],
            ['value' => (string) $longshiftSlots, 'group' => 'schedule', 'type' => 'number', 'label' => 'Auto Schedule Slot Longshift per Minggu']
        );

        $techniciansQuery = $this->scheduleUsersQuery();
        if (! empty($selectedUserIds)) {
            $techniciansQuery->whereIn('id', $selectedUserIds);
        }
        $technicians = $techniciansQuery->orderBy('name')->get();
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

        DB::transaction(function () use ($groups, $year, $weekNumbers, $shift1Slots, $shift2Slots, $longshiftSlots) {
            foreach ($groups as $users) {
                if ($users->isEmpty()) {
                    continue;
                }

                $userIds = $users->pluck('id')->values();
                $counts = [];
                foreach ($userIds as $id) {
                    $counts[$id] = ['s1' => 0, 's2' => 0, 'ls' => 0, 'assign' => 0];
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
                    $selectedLS = collect();

                    $desiredShiftForWeek = function (int $userId, int $weekIndex) use ($userIndex): string {
                        $idx = $userIndex[$userId] ?? 0;
                        $desired = ['piket', 'backup', 'longshift'];

                        return $desired[($idx + $weekIndex) % 3];
                    };

                    $pickCandidates = function (string $preferredShift) use (&$counts, $rotation, &$lastShift, $desiredShiftForWeek, $wIndex) {
                        return $rotation->sortBy(function ($id) use (&$counts, &$lastShift, $preferredShift, $desiredShiftForWeek, $wIndex) {
                            $repeatPenalty = (($lastShift[$id] ?? null) === $preferredShift) ? 1 : 0;
                            $desiredPenalty = ($desiredShiftForWeek((int) $id, (int) $wIndex) === $preferredShift) ? 0 : 1;

                            return ($counts[$id]['assign'] * 10000)
                                + ($desiredPenalty * 1000)
                                + ($repeatPenalty * 100)
                                + $counts[$id]['s1']
                                + $counts[$id]['s2']
                                + $counts[$id]['ls'];
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

                    foreach ($pickCandidates('longshift') as $id) {
                        if ($selectedLS->count() >= $longshiftSlots) {
                            break;
                        }
                        if ($selectedS1->contains($id) || $selectedS2->contains($id)) {
                            continue;
                        }
                        $selectedLS->push($id);
                        $counts[$id]['ls']++;
                        $counts[$id]['assign']++;
                    }

                    foreach ($userIds as $id) {
                        $status = TechnicianDailySchedule::STATUS_OFF;
                        if ($selectedS1->contains($id)) {
                            $status = TechnicianDailySchedule::STATUS_PIKET;
                        } elseif ($selectedS2->contains($id)) {
                            $status = TechnicianDailySchedule::STATUS_BACKUP;
                        } elseif ($selectedLS->contains($id)) {
                            $status = TechnicianDailySchedule::STATUS_LONGSHIFT;
                        }

                        if ($status !== TechnicianDailySchedule::STATUS_OFF) {
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
        Gate::authorize('schedule.manage');

        if (! Schema::hasTable('technician_daily_schedules')) {
            return redirect()->back()->with('error', 'Tabel jadwal harian belum ada. Jalankan: php artisan migrate');
        }

        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'off_days' => ['required', 'integer', 'min:0', 'max:10'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $offDays = (int) $request->input('off_days', 2);
        $selectedUserIds = $request->input('user_ids');

        $daysInMonth = (int) Carbon::createFromDate($year, $month, 1)->daysInMonth;
        if ($offDays >= $daysInMonth) {
            return redirect()->back()->with('error', 'Jumlah libur terlalu besar untuk bulan ini.');
        }

        Setting::updateOrCreate(
            ['key' => 'schedule_daily_off_days_per_month'],
            ['value' => (string) $offDays, 'group' => 'schedule', 'type' => 'number', 'label' => 'Libur per Bulan (Harian)']
        );

        $techniciansQuery = $this->scheduleUsersQuery();
        if (! empty($selectedUserIds)) {
            $techniciansQuery->whereIn('id', $selectedUserIds);
        }
        $technicians = $techniciansQuery->orderBy('name')->get();
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
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $selectedGroup = (string) $request->input('group', 'all');
        $selectedShift = (string) $request->input('shift', 'all');

        $techniciansQuery = $this->scheduleUsersQuery();
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            $techniciansQuery->where('id', Auth::id());
        }

        $technicians = $techniciansQuery->orderBy('name')->get();
        $this->applyScheduleDisplayNames($technicians);
        $this->applyScheduleMeta($technicians);
        $technicians = $this->deduplicateScheduleUsers($technicians);

        $shiftConfig = $this->getShiftConfig();

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

        if ($mode === 'daily') {
            if (! Schema::hasTable('technician_daily_schedules')) {
                return redirect()
                    ->route('schedules.index', ['year' => $year, 'month' => $month, 'mode' => 'weekly'])
                    ->with('error', 'Mode Harian membutuhkan migrasi database. Jalankan: php artisan migrate');
            }

            $startDateParam = $request->input('start_date');
            $endDateParam = $request->input('end_date');

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
                ->groupBy(fn (TechnicianDailySchedule $row) => $row->date->format('Y-m-d'))
                ->map(fn ($items) => $items->keyBy('user_id'));
            $dailySchedules = $this->mergeDailyWithWeeklyFallback(
                $dailySchedules,
                $userIds->all(),
                $rangeStart,
                $rangeEnd
            );

            if (in_array($selectedGroup, ['teknisi', 'wash', 'lainnya'], true)) {
                foreach ($groups as &$grp) {
                    if (($grp['key'] ?? '') !== $selectedGroup) {
                        $grp['users'] = collect();
                    }
                }
                unset($grp);
            }
            if (in_array($selectedShift, ['piket', 'backup', 'longshift', 'off'], true)) {
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

            $pdf = Pdf::loadView('schedules.pdf', compact(
                'mode',
                'technicians',
                'groups',
                'calendarWeeks',
                'dailySchedules',
                'year',
                'month',
                'shiftConfig'
            ))->setPaper('a4', 'landscape');

            return $pdf->download('jadwal-harian-'.sprintf('%04d-%02d', $year, $month).'.pdf');
        }

        // Weekly Mode
        $schedules = TechnicianSchedule::where('year', $year)
            ->get()
            ->groupBy('week_number')
            ->map(fn ($items) => $items->keyBy('user_id'));

        $periods = SchedulePeriod::where('year', $year)->get()->keyBy('week_number');
        $weeksData = $this->buildWeeksData($year, $month, $periods);

        if (in_array($selectedGroup, ['teknisi', 'wash', 'lainnya'], true)) {
            foreach ($groups as &$grp) {
                if (($grp['key'] ?? '') !== $selectedGroup) {
                    $grp['users'] = collect();
                }
            }
            unset($grp);
        }
        if (in_array($selectedShift, ['piket', 'backup', 'longshift', 'off'], true)) {
            $allowedIds = $schedules
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

        $pdf = Pdf::loadView('schedules.pdf', compact(
            'mode',
            'technicians',
            'groups',
            'weeksData',
            'schedules',
            'year',
            'month',
            'shiftConfig'
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
        $selectedGroup = (string) $request->input('group', 'all');
        $selectedShift = (string) $request->input('shift', 'all');

        $techniciansQuery = $this->scheduleUsersQuery();
        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            $techniciansQuery->where('id', Auth::id());
        }
        $technicians = $techniciansQuery->orderBy('name')->get();
        $this->applyScheduleDisplayNames($technicians);
        $this->applyScheduleMeta($technicians);
        $technicians = $this->deduplicateScheduleUsers($technicians);

        $shiftConfig = $this->getShiftConfig();

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
            $dailySchedules = TechnicianDailySchedule::query()
                ->whereIn('user_id', $userIds)
                ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->get()
                ->groupBy(fn (TechnicianDailySchedule $row) => $row->date->format('Y-m-d'))
                ->map(fn ($items) => $items->keyBy('user_id'));
            $dailySchedules = $this->mergeDailyWithWeeklyFallback(
                $dailySchedules,
                $userIds->all(),
                $start,
                $end
            );

            // Apply Group Filter
            if ($selectedGroup !== 'all') {
                $technicians = $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === $selectedGroup)->values();
            }

            // Apply Shift Filter
            if (in_array($selectedShift, ['piket', 'backup', 'longshift', 'off'], true)) {
                $allowedIds = $dailySchedules
                    ->flatMap(fn ($rows) => $rows)
                    ->filter(fn ($row) => ($row->status ?? 'off') === $selectedShift)
                    ->pluck('user_id')
                    ->unique()
                    ->values();
                $technicians = $technicians->filter(fn ($u) => $allowedIds->contains($u->id))->values();
            }

            return response()->streamDownload(function () use ($technicians, $dates, $dailySchedules) {
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
                        $dayRows = $dailySchedules->get($key);
                        $r = $dayRows ? $dayRows->get($tech->id) : null;
                        $status = $r?->status ?? TechnicianDailySchedule::STATUS_OFF;
                        $row[] = match ($status) {
                            TechnicianDailySchedule::STATUS_PIKET => 'S1',
                            TechnicianDailySchedule::STATUS_BACKUP => 'S2',
                            TechnicianDailySchedule::STATUS_LONGSHIFT => 'LS',
                            default => 'OFF',
                        };
                    }

                    $writer->addRow(Row::fromValues($row));
                }

                $writer->close();
            }, 'jadwal-harian-'.sprintf('%04d-%02d', $year, $month).'.xlsx');
        }

        // Weekly Excel
        $periods = SchedulePeriod::where('year', $year)->get()->keyBy('week_number');
        $weeksData = $this->buildWeeksData($year, $month, $periods);
        $weekNumbers = collect($weeksData)->pluck('week_number')->values();
        $schedules = TechnicianSchedule::where('year', $year)
            ->get()
            ->groupBy('week_number')
            ->map(fn ($items) => $items->keyBy('user_id'));

        // Apply Group Filter
        if ($selectedGroup !== 'all') {
            $technicians = $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === $selectedGroup)->values();
        }

        // Apply Shift Filter
        if (in_array($selectedShift, ['piket', 'backup', 'longshift', 'off'], true)) {
            $allowedIds = $schedules
                ->flatMap(fn ($rows) => $rows)
                ->filter(fn ($row) => ($row->status ?? 'off') === $selectedShift)
                ->pluck('user_id')
                ->unique()
                ->values();
            $technicians = $technicians->filter(fn ($u) => $allowedIds->contains($u->id))->values();
        }

        return response()->streamDownload(function () use ($technicians, $weekNumbers, $schedules, $shiftConfig) {
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
                    $schedule = $weekRows ? $weekRows->get($tech->id) : null;
                    $status = $schedule?->status ?? TechnicianSchedule::STATUS_OFF;
                    $groupKey = ($tech->schedule_group ?? '') === 'wash' ? 'wash' : 'teknisi';
                    $groupShift = $shiftConfig[$groupKey] ?? $shiftConfig['teknisi'];
                    $row[] = match ($status) {
                        TechnicianSchedule::STATUS_PIKET => 'S1 ('.$groupShift['shift_1_start'].'-'.$groupShift['shift_1_end'].')',
                        TechnicianSchedule::STATUS_BACKUP => 'S2 ('.$groupShift['shift_2_start'].'-'.$groupShift['shift_2_end'].')',
                        TechnicianSchedule::STATUS_LONGSHIFT => 'LS ('.($groupShift['longshift_start'] ?? '08:00').'-'.($groupShift['longshift_end'] ?? '20:00').')',
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
        Gate::authorize('schedule.manage');

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
                return TechnicianSchedule::STATUS_OFF;
            }
            if (in_array($v, ['S1', 'SHIFT1', 'SHIFT 1', '1', 'PIKET'], true)) {
                return TechnicianSchedule::STATUS_PIKET;
            }
            if (in_array($v, ['S2', 'SHIFT2', 'SHIFT 2', '2', 'BACKUP'], true)) {
                return TechnicianSchedule::STATUS_BACKUP;
            }
            if (in_array($v, ['LS', 'LONGSHIFT', 'LONG SHIFT', 'L', '3'], true)) {
                return TechnicianSchedule::STATUS_LONGSHIFT;
            }
            if (str_contains($v, 'S1')) {
                return TechnicianSchedule::STATUS_PIKET;
            }
            if (str_contains($v, 'S2')) {
                return TechnicianSchedule::STATUS_BACKUP;
            }
            if (str_contains($v, 'LS') || str_contains($v, 'LONG')) {
                return TechnicianSchedule::STATUS_LONGSHIFT;
            }

            return TechnicianSchedule::STATUS_OFF;
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
        Gate::authorize('schedule.manage');

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
        Gate::authorize('schedule.manage');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'week_number' => 'required|integer|min:1|max:53',
            'year' => 'required|integer',
            'status' => 'required|in:piket,off,backup,longshift',
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
        Gate::authorize('schedule.manage');

        if (! Schema::hasTable('technician_daily_schedules')) {
            return redirect()->back()->with('error', 'Tabel jadwal harian belum ada. Jalankan: php artisan migrate');
        }

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:piket,off,backup,longshift'],
        ]);

        TechnicianDailySchedule::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'date' => \Carbon\Carbon::parse($request->date)->toDateString(),
            ],
            [
                'status' => $request->status,
                'notes' => null,
            ]
        );

        return redirect()->back()->with('success', __('Schedule updated successfully.'));
    }

    public function bulkStore(Request $request)
    {
        Gate::authorize('schedule.manage');

        $request->validate([
            'year' => ['required', 'integer'],
            'schedules' => ['required', 'array'],
            'schedules.*' => ['array'],
            'schedules.*.*' => ['required', 'in:piket,off,backup,longshift'],
        ]);

        $year = (int) $request->input('year');
        $schedules = $request->input('schedules');

        DB::transaction(function () use ($year, $schedules) {
            foreach ($schedules as $userId => $weeks) {
                foreach ($weeks as $weekNumber => $status) {
                    TechnicianSchedule::updateOrCreate(
                        ['user_id' => $userId, 'week_number' => $weekNumber, 'year' => $year],
                        ['status' => $status, 'notes' => null]
                    );
                }
            }
        });

        return redirect()->back()->with('success', 'Perubahan jadwal mingguan berhasil disimpan.');
    }

    public function dailyBulkStore(Request $request)
    {
        Gate::authorize('schedule.manage');

        $request->validate([
            'schedules' => ['required', 'array'],
            'schedules.*' => ['array'],
            'schedules.*.*' => ['required', 'in:piket,off,backup,longshift'],
        ]);

        $schedules = $request->input('schedules');

        DB::transaction(function () use ($schedules) {
            foreach ($schedules as $userId => $dates) {
                foreach ($dates as $date => $status) {
                    // Gunakan toDateString() untuk memastikan format YYYY-MM-DD yang konsisten dengan SQLite
                    $formattedDate = \Carbon\Carbon::parse($date)->toDateString();
                    
                    TechnicianDailySchedule::updateOrCreate(
                        ['user_id' => $userId, 'date' => $formattedDate],
                        ['status' => $status, 'notes' => null]
                    );
                }
            }
        });

        return redirect()->back()->with('success', 'Perubahan jadwal harian berhasil disimpan.');
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
            ['key' => 'schedule_teknisi_shift_1_start', 'value' => '08:00', 'label' => 'Jadwal Teknisi Shift 1 Mulai'],
            ['key' => 'schedule_teknisi_shift_1_end', 'value' => '17:00', 'label' => 'Jadwal Teknisi Shift 1 Selesai'],
            ['key' => 'schedule_teknisi_shift_2_start', 'value' => '15:00', 'label' => 'Jadwal Teknisi Shift 2 Mulai'],
            ['key' => 'schedule_teknisi_shift_2_end', 'value' => '00:00', 'label' => 'Jadwal Teknisi Shift 2 Selesai'],
            ['key' => 'schedule_teknisi_longshift_start', 'value' => '08:00', 'label' => 'Jadwal Teknisi Longshift Mulai'],
            ['key' => 'schedule_teknisi_longshift_end', 'value' => '20:00', 'label' => 'Jadwal Teknisi Longshift Selesai'],
            ['key' => 'schedule_wash_shift_1_start', 'value' => '08:00', 'label' => 'Jadwal Operator Wash Shift 1 Mulai'],
            ['key' => 'schedule_wash_shift_1_end', 'value' => '17:00', 'label' => 'Jadwal Operator Wash Shift 1 Selesai'],
            ['key' => 'schedule_wash_shift_2_start', 'value' => '13:00', 'label' => 'Jadwal Operator Wash Shift 2 Mulai'],
            ['key' => 'schedule_wash_shift_2_end', 'value' => '22:00', 'label' => 'Jadwal Operator Wash Shift 2 Selesai'],
            ['key' => 'schedule_wash_longshift_start', 'value' => '08:00', 'label' => 'Jadwal Operator Wash Longshift Mulai'],
            ['key' => 'schedule_wash_longshift_end', 'value' => '20:00', 'label' => 'Jadwal Operator Wash Longshift Selesai'],
        ];

        foreach ($defaults as $item) {
            Setting::firstOrCreate(
                ['key' => $item['key']],
                [
                    'value' => $item['value'],
                    'group' => str_starts_with($item['key'], 'schedule_') ? 'schedule' : 'attendance',
                    'type' => 'time',
                    'label' => $item['label'],
                ]
            );
        }
    }

    private function getShiftConfig(): array
    {
        return [
            'teknisi' => [
                'label' => 'Teknisi',
                'shift_1_start' => Setting::getValue('schedule_teknisi_shift_1_start', '08:00'),
                'shift_1_end' => Setting::getValue('schedule_teknisi_shift_1_end', '17:00'),
                'shift_2_start' => Setting::getValue('schedule_teknisi_shift_2_start', '15:00'),
                'shift_2_end' => Setting::getValue('schedule_teknisi_shift_2_end', '00:00'),
                'longshift_start' => Setting::getValue('schedule_teknisi_longshift_start', '08:00'),
                'longshift_end' => Setting::getValue('schedule_teknisi_longshift_end', '20:00'),
            ],
            'wash' => [
                'label' => 'Operator Wash',
                'shift_1_start' => Setting::getValue('schedule_wash_shift_1_start', '08:00'),
                'shift_1_end' => Setting::getValue('schedule_wash_shift_1_end', '17:00'),
                'shift_2_start' => Setting::getValue('schedule_wash_shift_2_start', '13:00'),
                'shift_2_end' => Setting::getValue('schedule_wash_shift_2_end', '22:00'),
                'longshift_start' => Setting::getValue('schedule_wash_longshift_start', '08:00'),
                'longshift_end' => Setting::getValue('schedule_wash_longshift_end', '20:00'),
            ],
        ];
    }

    private function ensureAutoScheduleSettings(): void
    {
        $defaults = [
            ['key' => 'schedule_auto_shift1_slots', 'value' => '1', 'label' => 'Auto Schedule Slot Shift 1 per Minggu'],
            ['key' => 'schedule_auto_shift2_slots', 'value' => '1', 'label' => 'Auto Schedule Slot Shift 2 per Minggu'],
            ['key' => 'schedule_auto_longshift_slots', 'value' => '0', 'label' => 'Auto Schedule Slot Longshift per Minggu'],
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
        $totalUsers = count($userIds);
        $totalDays = count($days);

        if ($totalUsers === 0) {
            return [];
        }

        // Step 1: Tentukan hari OFF untuk setiap user terlebih dahulu (OFF First Strategy)
        $userOffDays = [];
        $dayOffCount = array_fill(0, $totalDays, 0);

        foreach ($userIds as $uIdx => $userId) {
            $offAssigned = 0;
            $attempts = 0;
            $userOffDays[$userId] = [];

            // Coba sebar OFF dengan jarak minimal (misal 5 hari)
            // Menggunakan algoritma sebaran user: (uIdx * 3 + j * 7) sebagai basis
            for ($j = 0; $j < $offDaysPerUser; $j++) {
                $found = false;
                // Coba beberapa offset jika slot hari tersebut sudah terlalu banyak yang OFF
                for ($offset = 0; $offset < $totalDays; $offset++) {
                    $dIdx = ($uIdx * 7 + $j * 11 + $offset) % $totalDays;
                    
                    // Cek apakah hari ini sudah terlalu banyak yang OFF (max 25% dari total user)
                    $maxOffToday = max(1, (int) ceil($totalUsers * 0.25));
                    
                    // Cek jarak dengan OFF yang sudah ada untuk user ini
                    $tooClose = false;
                    foreach ($userOffDays[$userId] as $existingDIdx) {
                        if (abs($existingDIdx - $dIdx) < 5) {
                            $tooClose = true;
                            break;
                        }
                    }

                    if ($dayOffCount[$dIdx] < $maxOffToday && !$tooClose) {
                        $userOffDays[$userId][] = $dIdx;
                        $dayOffCount[$dIdx]++;
                        $found = true;
                        break;
                    }
                }

                // Jika tidak ketemu slot ideal, ambil yang paling longgar
                if (!$found) {
                    $bestDIdx = 0;
                    $minCount = 999;
                    for ($d = 0; $d < $totalDays; $d++) {
                        if ($dayOffCount[$d] < $minCount) {
                            $minCount = $dayOffCount[$d];
                            $bestDIdx = $d;
                        }
                    }
                    $userOffDays[$userId][] = $bestDIdx;
                    $dayOffCount[$bestDIdx]++;
                }
            }
        }

        // Step 2: Isi S1 & S2 untuk sisa user yang tidak OFF
        $plan = [];
        $weekStart = $start->copy()->startOfWeek();

        foreach ($days as $dIdx => $day) {
            $dateKey = $day->format('Y-m-d');
            $weekIndex = (int) floor($weekStart->diffInDays($day->copy()->startOfWeek()) / 7);
            
            $offUsersToday = [];
            foreach ($userIds as $userId) {
                if (in_array($dIdx, $userOffDays[$userId])) {
                    $offUsersToday[$userId] = true;
                }
            }

            $availableUsers = [];
            foreach ($userIds as $userId) {
                if (!isset($offUsersToday[$userId])) {
                    $availableUsers[] = $userId;
                }
            }

            // Shuffle atau rotasi biar adil untuk penentuan S1/S2
            // Kita gunakan rotasi berbasis dIdx agar konsisten tapi tetap bergantian
            $nAvailable = count($availableUsers);
            if ($nAvailable > 0) {
                $rotationOffset = $dIdx % $nAvailable;
                $rotated = array_merge(
                    array_slice($availableUsers, $rotationOffset),
                    array_slice($availableUsers, 0, $rotationOffset)
                );

                // Asumsi: S1 dan S2 dibagi rata dari yang tersedia
                // Misal: 50% S1, sisanya S2
                $s1Limit = (int) ceil($nAvailable / 2);

                $statuses = [];
                foreach ($userIds as $userId) {
                    if (isset($offUsersToday[$userId])) {
                        $statuses[$userId] = TechnicianDailySchedule::STATUS_OFF;
                    } else {
                        // Cari posisi di array rotated
                        $pos = array_search($userId, $rotated);
                        $statuses[$userId] = ($pos < $s1Limit) 
                            ? TechnicianDailySchedule::STATUS_PIKET 
                            : TechnicianDailySchedule::STATUS_BACKUP;
                    }
                }
                $plan[$dateKey] = $statuses;
            } else {
                // Kasus langka: semua OFF (tidak mungkin dengan limit maxOffToday)
                $statuses = [];
                foreach ($userIds as $userId) {
                    $statuses[$userId] = TechnicianDailySchedule::STATUS_OFF;
                }
                $plan[$dateKey] = $statuses;
            }
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
                try {
                    $baseUsername = 'emp'.$employee->id;
                    $username = $baseUsername;
                    $counter = 1;
                    while (User::query()->where('username', $username)->exists()) {
                        $username = $baseUsername.'_'.strtolower(substr(md5((string) ($employee->id + $counter)), 0, 4));
                        $counter++;
                        if ($counter > 10) {
                            $username = $baseUsername.'_'.Str::random(4);
                        }
                    }

                    $email = $employee->email;
                    if ($email && User::query()->where('email', $email)->exists()) {
                        $email = null;
                    }
                    if (! $email) {
                        $email = $username.'@mstore.local';
                    }
                    
                    $baseEmail = $email;
                    $emailCounter = 1;
                    while (User::query()->where('email', $email)->exists()) {
                        $parts = explode('@', $baseEmail);
                        $email = $parts[0].'_'.Str::random(4).'@'.($parts[1] ?? 'mstore.local');
                        $emailCounter++;
                        if ($emailCounter > 10) break;
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

                    DB::transaction(function () use ($employee, $username, $email, $roleId) {
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
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to auto-create user for employee ID '.$employee->id.': '.$e->getMessage());
                }
            });

        WashEmployee::query()
            ->whereNull('user_id')
            ->orderBy('id')
            ->each(function (WashEmployee $washEmployee) use ($roleIds, $fallbackRoleId) {
                try {
                    $baseUsername = 'wash'.$washEmployee->id;
                    $username = $baseUsername;
                    $counter = 1;
                    while (User::query()->where('username', $username)->exists()) {
                        $username = $baseUsername.'_'.strtolower(substr(md5((string) ($washEmployee->id + $counter)), 0, 4));
                        $counter++;
                        if ($counter > 10) {
                            $username = $baseUsername.'_'.Str::random(4);
                        }
                    }

                    $roleId = $roleIds['karyawan-wash'] ?? $fallbackRoleId;

                    $email = $username.'@mstore.local';
                    $emailCounter = 1;
                    while (User::query()->where('email', $email)->exists()) {
                        $email = $username.'_'.Str::random(4).'@mstore.local';
                        $emailCounter++;
                        if ($emailCounter > 10) break;
                    }

                    DB::transaction(function () use ($washEmployee, $username, $email, $roleId) {
                        $user = User::create([
                            'name' => $washEmployee->name,
                            'username' => $username,
                            'email' => $email,
                            'phone' => $washEmployee->phone,
                            'password' => Hash::make('password'),
                            'role_id' => $roleId,
                            'is_active' => true,
                        ]);

                        $washEmployee->user_id = $user->id;
                        $washEmployee->save();
                    });
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to auto-create user for wash employee ID '.$washEmployee->id.': '.$e->getMessage());
                }
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

        $shiftConfig = $this->getShiftConfig();

        return [$technicians, $weeks, $year, $month, $shiftConfig];
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

    private function mergeDailyWithWeeklyFallback(
        \Illuminate\Support\Collection $dailySchedules,
        array $userIds,
        Carbon $startDate,
        Carbon $endDate
    ): \Illuminate\Support\Collection {
        if (empty($userIds)) {
            return $dailySchedules;
        }

        $candidateYears = collect([$startDate->year, $endDate->year, $startDate->weekYear, $endDate->weekYear])
            ->map(fn ($y) => (int) $y)
            ->unique()
            ->values();

        $weeklyRows = TechnicianSchedule::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('year', $candidateYears)
            ->get(['user_id', 'year', 'week_number', 'status']);

        $weeklyByUser = [];
        foreach ($weeklyRows as $row) {
            $year = (int) $row->year;
            $week = (int) $row->week_number;
            $uid = (int) $row->user_id;
            $weeklyByUser[$uid][$year][$week] = (string) $row->status;
        }

        $merged = $dailySchedules;
        for ($day = $startDate->copy(); $day->lte($endDate); $day->addDay()) {
            $dayKey = $day->format('Y-m-d');
            $week = (int) $day->weekOfYear;
            $weekYear = (int) $day->weekYear;
            $calendarYear = (int) $day->year;
            $rows = $merged->get($dayKey, collect());

            foreach ($userIds as $userId) {
                $uid = (int) $userId;
                if ($rows->has($uid)) {
                    continue;
                }

                $fallbackStatus = $weeklyByUser[$uid][$weekYear][$week]
                    ?? $weeklyByUser[$uid][$calendarYear][$week]
                    ?? null;
                if (! in_array($fallbackStatus, [
                    TechnicianSchedule::STATUS_PIKET,
                    TechnicianSchedule::STATUS_BACKUP,
                    TechnicianSchedule::STATUS_LONGSHIFT,
                    TechnicianSchedule::STATUS_OFF,
                ], true)) {
                    continue;
                }

                $rows->put($uid, new TechnicianDailySchedule([
                    'user_id' => $uid,
                    'date' => $dayKey,
                    'status' => $fallbackStatus,
                    'notes' => null,
                ]));
            }

            $merged->put($dayKey, $rows);
        }

        return $merged;
    }
}
