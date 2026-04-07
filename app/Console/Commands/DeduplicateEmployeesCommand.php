<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\TechnicianAttendance;
use App\Models\TechnicianSchedule;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicateEmployeesCommand extends Command
{
    protected $signature = 'employees:deduplicate {--dry-run : Simulasi tanpa menyimpan perubahan}';

    protected $description = 'Bersihkan data karyawan dan user duplikat berdasarkan user_id/email/nama secara aman';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $stats = [
            'employee_merged_groups' => 0,
            'employee_deleted_rows' => 0,
            'user_deactivated' => 0,
            'schedule_relinked' => 0,
            'attendance_relinked' => 0,
        ];

        $runner = function () use (&$stats, $dryRun): void {
            $this->dedupeEmployeesByUserId($stats, $dryRun);
            $this->dedupeEmployeesByWashName($stats, $dryRun);
            $this->deactivateOrphanGeneratedUsers($stats, $dryRun);
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        $this->info('Selesai deduplikasi data karyawan');
        foreach ($stats as $key => $value) {
            $this->line(" - {$key}: {$value}");
        }
        $this->line(' - mode: '.($dryRun ? 'dry-run' : 'apply'));

        return self::SUCCESS;
    }

    private function dedupeEmployeesByUserId(array &$stats, bool $dryRun): void
    {
        $duplicateUserIds = Employee::query()
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as c')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $rows = Employee::query()->where('user_id', $userId)->orderBy('id')->get();
            if ($rows->count() <= 1) {
                continue;
            }

            $keeper = $rows->sortByDesc(fn (Employee $employee) => $this->score($employee))->first();
            $duplicates = $rows->where('id', '!=', $keeper->id)->values();

            foreach ($duplicates as $duplicate) {
                $this->mergeEmployee($keeper, $duplicate);
            }

            if (! $dryRun) {
                $keeper->save();
                Employee::query()->whereIn('id', $duplicates->pluck('id')->all())->delete();
            }

            $stats['employee_merged_groups']++;
            $stats['employee_deleted_rows'] += $duplicates->count();
            $this->line("merge employee user_id={$userId} keep={$keeper->id} remove=".$duplicates->pluck('id')->implode(','));
        }
    }

    private function deactivateOrphanGeneratedUsers(array &$stats, bool $dryRun): void
    {
        $linkedUserIds = Employee::query()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->all();

        $orphans = User::query()
            ->where('is_active', true)
            ->whereNotIn('id', $linkedUserIds)
            ->where(function ($query) {
                $query->where('username', 'like', 'emp%')
                    ->orWhere('username', 'like', 'wash%');
            })
            ->orderBy('id')
            ->get();

        foreach ($orphans as $orphan) {
            $target = User::query()
                ->where('id', '!=', $orphan->id)
                ->whereIn('id', $linkedUserIds)
                ->whereRaw('LOWER(name) = ?', [strtolower((string) $orphan->name)])
                ->first();

            if ($target) {
                if (! $dryRun) {
                    $relinkedSchedule = TechnicianSchedule::query()
                        ->where('user_id', $orphan->id)
                        ->update(['user_id' => $target->id]);
                    $relinkedAttendance = TechnicianAttendance::query()
                        ->where('user_id', $orphan->id)
                        ->update(['user_id' => $target->id]);
                    $stats['schedule_relinked'] += $relinkedSchedule;
                    $stats['attendance_relinked'] += $relinkedAttendance;
                } else {
                    $stats['schedule_relinked'] += TechnicianSchedule::query()->where('user_id', $orphan->id)->count();
                    $stats['attendance_relinked'] += TechnicianAttendance::query()->where('user_id', $orphan->id)->count();
                }
            }

            if (! $dryRun) {
                $orphan->is_active = false;
                $orphan->save();
            }
            $stats['user_deactivated']++;
            $this->line('deactivate orphan user #'.$orphan->id.' name='.$orphan->name.($target ? ' -> relink to #'.$target->id : ''));
        }
    }

    private function dedupeEmployeesByWashName(array &$stats, bool $dryRun): void
    {
        $dupNames = Employee::query()
            ->selectRaw('LOWER(TRIM(full_name)) as full_name_key, COUNT(*) as c')
            ->groupBy('full_name_key')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('full_name_key');

        foreach ($dupNames as $nameKey) {
            $rows = Employee::query()
                ->whereRaw('LOWER(TRIM(full_name)) = ?', [$nameKey])
                ->orderBy('id')
                ->get();

            if ($rows->count() <= 1) {
                continue;
            }

            $isWashLike = $rows->contains(fn (Employee $employee) => (bool) $employee->wash_employee_id)
                || $rows->contains(fn (Employee $employee) => str_starts_with(strtolower((string) $employee->email), 'wash-'));
            if (! $isWashLike) {
                continue;
            }

            $keeper = $rows->sortByDesc(fn (Employee $employee) => $this->score($employee))->first();
            $duplicates = $rows->where('id', '!=', $keeper->id)->values();
            if ($duplicates->isEmpty()) {
                continue;
            }

            foreach ($duplicates as $duplicate) {
                $this->mergeEmployee($keeper, $duplicate);

                if ($duplicate->user_id && $keeper->user_id && $duplicate->user_id !== $keeper->user_id) {
                    if (! $dryRun) {
                        $stats['schedule_relinked'] += TechnicianSchedule::query()
                            ->where('user_id', $duplicate->user_id)
                            ->update(['user_id' => $keeper->user_id]);
                        $stats['attendance_relinked'] += TechnicianAttendance::query()
                            ->where('user_id', $duplicate->user_id)
                            ->update(['user_id' => $keeper->user_id]);
                    } else {
                        $stats['schedule_relinked'] += TechnicianSchedule::query()->where('user_id', $duplicate->user_id)->count();
                        $stats['attendance_relinked'] += TechnicianAttendance::query()->where('user_id', $duplicate->user_id)->count();
                    }
                }
            }

            if (! $dryRun) {
                $keeper->save();
                Employee::query()->whereIn('id', $duplicates->pluck('id')->all())->delete();
            }

            $stats['employee_merged_groups']++;
            $stats['employee_deleted_rows'] += $duplicates->count();
            $this->line("merge employee name={$nameKey} keep={$keeper->id} remove=".$duplicates->pluck('id')->implode(','));
        }
    }

    private function mergeEmployee(Employee $keeper, Employee $duplicate): void
    {
        if (! $keeper->wash_employee_id && $duplicate->wash_employee_id) {
            $keeper->wash_employee_id = $duplicate->wash_employee_id;
        }
        if (($keeper->phone === null || $keeper->phone === '' || $keeper->phone === '-') && $duplicate->phone && $duplicate->phone !== '-') {
            $keeper->phone = $duplicate->phone;
        }
        if (($keeper->address === null || trim((string) $keeper->address) === '' || trim((string) $keeper->address) === '-') && $duplicate->address && trim((string) $duplicate->address) !== '-') {
            $keeper->address = $duplicate->address;
        }
        if (($keeper->nik === null || trim((string) $keeper->nik) === '' || str_starts_with((string) $keeper->nik, 'AUTO-')) && $duplicate->nik && ! str_starts_with((string) $duplicate->nik, 'AUTO-')) {
            $keeper->nik = $duplicate->nik;
        }
        if (($keeper->email === null || trim((string) $keeper->email) === '' || str_ends_with(strtolower((string) $keeper->email), '@mstore.local')) && $duplicate->email) {
            $keeper->email = $duplicate->email;
        }
        if (($keeper->position === null || trim((string) $keeper->position) === '' || trim((string) $keeper->position) === 'Karyawan') && $duplicate->position) {
            $keeper->position = $duplicate->position;
        }
        if (($keeper->department === null || trim((string) $keeper->department) === '' || trim((string) $keeper->department) === 'Operasional') && $duplicate->department) {
            $keeper->department = $duplicate->department;
        }
    }

    private function score(Employee $employee): float
    {
        $score = 0.0;
        if ($employee->wash_employee_id) {
            $score += 100;
        }
        if ($employee->phone && $employee->phone !== '-') {
            $score += 25;
        }
        if ($employee->address && trim((string) $employee->address) !== '' && trim((string) $employee->address) !== '-') {
            $score += 15;
        }
        if ($employee->nik && ! str_starts_with((string) $employee->nik, 'AUTO-')) {
            $score += 20;
        }
        if ($employee->email && ! str_ends_with(strtolower((string) $employee->email), '@mstore.local')) {
            $score += 10;
        }
        if ($employee->position && ! in_array(strtolower((string) $employee->position), ['karyawan', 'technician'], true)) {
            $score += 5;
        }
        $score += ((int) $employee->id) / 1000;

        return $score;
    }
}
