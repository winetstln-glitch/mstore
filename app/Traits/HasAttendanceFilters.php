<?php

namespace App\Traits;

use App\Models\SalaryAdjustment;
use App\Models\TechnicianAttendance;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait HasAttendanceFilters
{
    /**
     * Get filtered attendance query
     */
    protected function getFilteredAttendanceQuery(Request $request)
    {
        $query = TechnicianAttendance::with('user');

        if ($request->filled('date')) {
            $query->whereDate('clock_in', $request->date);
        }

        if ($request->filled('month')) {
            $query->whereMonth('clock_in', date('m', strtotime($request->month)))
                ->whereYear('clock_in', date('Y', strtotime($request->month)));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if (! $this->canViewAllAttendanceData()) {
            $query->where('user_id', Auth::id());
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return $query;
    }

    /**
     * Get filtered salary adjustments query
     */
    protected function getFilteredAdjustmentsQuery(Request $request, $status = null)
    {
        $query = SalaryAdjustment::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('month')) {
            $query->whereMonth('date', date('m', strtotime($request->month)))
                ->whereYear('date', date('Y', strtotime($request->month)));
        }

        if (! $this->canViewAllAttendanceData()) {
            $query->where('user_id', Auth::id());
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return $query;
    }

    /**
     * Calculate summary from attendances and adjustments
     */
    protected function calculateAttendanceSummary($attendances, $allAdjustments)
    {
        return $attendances->groupBy('user_id')->map(function ($items) use ($allAdjustments) {
            $user = $items->first()->user;

            $presentCount = $items->whereIn('status', ['present', 'late'])->count();
            $leaveCount = $items->where('status', 'leave')->count();
            $permitCount = $items->where('status', 'permit')->count();
            $sickCount = $items->where('status', 'sick')->count();
            $alphaCount = $items->where('status', 'alpha')->count();

            // Total hari yang dibayar (Hadir + Izin + Cuti + Sakit)
            $paidDays = $presentCount + $leaveCount + $permitCount + $sickCount;
            
            // Resolve Daily Salary
            $workingDays = (int) Setting::getValue('attendance_working_days', 28);
            if ($user->daily_salary > 0) {
                $dailySalary = $user->daily_salary;
            } elseif ($user->monthly_salary > 0) {
                $dailySalary = $user->monthly_salary / $workingDays;
            } else {
                $dailySalary = 0;
            }

            $userAdjustments = $allAdjustments->get($user->id, collect());
            $totalBonus = $userAdjustments->where('type', 'bonus')->sum('amount');
            
            // Calculate Specific Bonuses based on description keywords
            $bonusDisiplin = $userAdjustments->where('type', 'bonus')
                ->filter(fn($a) => str_contains(strtolower($a->description ?? ''), 'disiplin'))
                ->sum('amount');
            $bonusTanggungJawab = $userAdjustments->where('type', 'bonus')
                ->filter(fn($a) => str_contains(strtolower($a->description ?? ''), 'tanggung'))
                ->sum('amount');
            $bonusAbsensi = $userAdjustments->where('type', 'bonus')
                ->filter(fn($a) => str_contains(strtolower($a->description ?? ''), 'absensi'))
                ->sum('amount');
            
            // Bonus Lainnya (Total Bonus - Specific Bonuses)
            $bonusLainnya = $totalBonus - ($bonusDisiplin + $bonusTanggungJawab + $bonusAbsensi);
            
            // Calculate Specific Kasbon based on description keywords
            $totalKasbon = $userAdjustments->where('type', 'kasbon')->sum('amount');
            
            $kasbonKantor = $userAdjustments->where('type', 'kasbon')
                ->filter(fn($a) => str_contains(strtolower($a->description ?? ''), 'kantor'))
                ->sum('amount');
            $kasbonWarung = $userAdjustments->where('type', 'kasbon')
                ->filter(fn($a) => str_contains(strtolower($a->description ?? ''), 'warung'))
                ->sum('amount');
            
            // Kasbon Lainnya (Total Kasbon - Specific Kasbons)
            $kasbonLainnya = $totalKasbon - ($kasbonKantor + $kasbonWarung);

            return [
                'user' => $user,
                'present_count' => $presentCount,
                'leave_count' => $leaveCount,
                'permit_count' => $permitCount,
                'sick_count' => $sickCount,
                'alpha_count' => $alphaCount,
                'paid_days' => $paidDays,
                'daily_salary' => $dailySalary,
                'bonus_disiplin' => $bonusDisiplin,
                'bonus_tanggung_jawab' => $bonusTanggungJawab,
                'bonus_absensi' => $bonusAbsensi,
                'bonus_lainnya' => $bonusLainnya,
                'total_bonus' => $totalBonus,
                'kasbon_kantor' => $kasbonKantor,
                'kasbon_warung' => $kasbonWarung,
                'kasbon_lainnya' => $kasbonLainnya,
                'total_kasbon' => $totalKasbon,
                'total_salary' => ($paidDays * $dailySalary) + $totalBonus - $totalKasbon,
                'dates' => $items,
                'adjustments' => $userAdjustments,
            ];
        });
    }
}
