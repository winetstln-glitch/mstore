<?php

namespace App\Http\Controllers;

use App\Actions\Schedule\AutoGenerateScheduleAction;
use App\Http\Requests\Schedule\AutoGenerateScheduleRequest;
use App\Models\SchedulePeriod;
use App\Models\TechnicianDailySchedule;
use App\Models\TechnicianSchedule;
use App\Services\Schedule\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class ScheduleRefactoredController extends Controller
{
    public function __construct(
        private readonly ScheduleService $scheduleService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('schedule.view');

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $mode = (string) $request->input('mode', 'weekly');
        $selectedGroup = (string) $request->input('group', 'all');
        $selectedShift = (string) $request->input('shift', 'all');

        $techniciansQuery = $this->scheduleService->getScheduleUsersQuery();

        if (! Auth::user()->hasPermission('schedule.manage') && ! Auth::user()->hasRole('admin')) {
            $techniciansQuery->where('id', Auth::id());
        }

        $technicians = $techniciansQuery->orderBy('name')->get();
        $this->scheduleService->applyScheduleDisplayNames($technicians);
        $this->scheduleService->applyScheduleMeta($technicians);
        $technicians = $this->scheduleService->deduplicateScheduleUsers($technicians);

        $periods = SchedulePeriod::where('year', $year)->get()->keyBy('week_number');
        $weeksData = $this->scheduleService->buildWeeksData($year, $month, $periods);
        $shiftConfig = $this->scheduleService->getShiftConfig();

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

        $dailySchedules = collect();
        $calendarWeeks = [];
        if ($mode === 'daily') {
            if (! Schema::hasTable('technician_daily_schedules')) {
                return redirect()->route('schedules.index', ['year' => $year, 'month' => $month, 'mode' => 'weekly'])
                    ->with('error', 'Mode Harian membutuhkan migrasi database. Jalankan: php artisan migrate');
            }

            $startDateParam = $request->input('start_date');
            $endDateParam = $request->input('end_date');
            $defaultStart = Carbon::createFromDate((int) $year, (int) $month, 1)->startOfDay();
            $defaultEnd = Carbon::createFromDate((int) $year, (int) $month, 1)->endOfMonth()->startOfDay();
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
                    $year = (int) $rangeStart->year;
                    $month = (int) $rangeStart->month;
                } catch (\Throwable $e) {
                }
            }

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
        }

        $schedules = TechnicianSchedule::where('year', $year)
            ->with('user')
            ->get()
            ->groupBy('week_number')
            ->map(fn ($items) => $items->keyBy('user_id'));

        if (in_array($selectedGroup, ['teknisi', 'wash', 'lainnya'], true)) {
            foreach ($groups as &$grp) {
                if (($grp['key'] ?? '') !== $selectedGroup) {
                    $grp['users'] = collect();
                }
            }
            unset($grp);
        }

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
            'selectedGroup',
            'selectedShift',
            'shiftConfig'
        ));
    }

    public function autoGenerate(AutoGenerateScheduleRequest $request, AutoGenerateScheduleAction $action)
    {
        Gate::authorize('schedule.manage');

        $validated = $request->validated();

        $action->execute(
            year: (int) $validated['year'],
            month: (int) $validated['month'],
            shift1Slots: (int) ($validated['shift1_slots'] ?? 1),
            shift2Slots: (int) ($validated['shift2_slots'] ?? 1),
            longshiftSlots: (int) ($validated['longshift_slots'] ?? 0),
            selectedUserIds: $validated['user_ids'] ?? null
        );

        return redirect()->route('schedules.index', ['year' => $validated['year'], 'month' => $validated['month']])
            ->with('success', 'Auto schedule berhasil dibuat untuk bulan ini.');
    }
}
