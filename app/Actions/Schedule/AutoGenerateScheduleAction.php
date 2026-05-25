<?php

namespace App\Actions\Schedule;

use App\Models\SchedulePeriod;
use App\Models\Setting;
use App\Models\TechnicianDailySchedule;
use App\Models\TechnicianSchedule;
use App\Services\Schedule\ScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AutoGenerateScheduleAction
{
    public function __construct(
        private readonly ScheduleService $scheduleService
    ) {}

    public function execute(
        int $year,
        int $month,
        int $shift1Slots,
        int $shift2Slots,
        int $longshiftSlots,
        ?array $selectedUserIds = null
    ): void {
        $shift1Slots = max(1, $shift1Slots);
        $shift2Slots = max(1, $shift2Slots);
        $longshiftSlots = max(0, $longshiftSlots);

        $this->updateSettings($shift1Slots, $shift2Slots, $longshiftSlots);

        $techniciansQuery = $this->scheduleService->getScheduleUsersQuery();
        if (! empty($selectedUserIds)) {
            $techniciansQuery->whereIn('id', $selectedUserIds);
        }
        $technicians = $techniciansQuery->orderBy('name')->get();
        $this->scheduleService->applyScheduleDisplayNames($technicians);
        $this->scheduleService->applyScheduleMeta($technicians);
        $technicians = $this->scheduleService->deduplicateScheduleUsers($technicians);

        $periods = SchedulePeriod::where('year', $year)->get()->keyBy('week_number');
        $weeksData = $this->scheduleService->buildWeeksData($year, $month, $periods);
        $weekNumbers = collect($weeksData)->pluck('week_number')->values();

        $groups = [
            $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === 'teknisi')->values(),
            $technicians->filter(fn ($u) => ($u->schedule_group ?? '') === 'wash')->values(),
            $technicians->filter(fn ($u) => ! in_array(($u->schedule_group ?? ''), ['teknisi', 'wash'], true))->values(),
        ];

        DB::transaction(function () use ($groups, $year, $weekNumbers, $shift1Slots, $shift2Slots, $longshiftSlots, $periods) {
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

                        $period = $periods->get($weekNumber);
                        if ($period) {
                            $start = Carbon::parse($period->start_date)->startOfDay();
                            $end = Carbon::parse($period->end_date)->startOfDay();
                        } else {
                            $start = Carbon::now()->setISODate($year, $weekNumber)->startOfWeek()->startOfDay();
                            $end = $start->copy()->endOfWeek()->startOfDay();
                        }

                        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                            TechnicianDailySchedule::updateOrCreate(
                                ['user_id' => $id, 'date' => $date->toDateString()],
                                ['status' => $status, 'notes' => null]
                            );
                        }
                    }
                }
            }
        });
    }

    private function updateSettings(int $shift1Slots, int $shift2Slots, int $longshiftSlots): void
    {
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
    }
}
