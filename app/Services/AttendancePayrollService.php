<?php

namespace App\Services;

use App\Models\SalaryAdjustment;
use App\Models\Setting;
use Illuminate\Http\Request;

class AttendancePayrollService
{
    public function calculateAttendanceSummary($attendances, $allAdjustments, Request $request = null): \Illuminate\Support\Collection
    {
        return $attendances->groupBy('user_id')->map(function ($items) use ($allAdjustments, $request) {
            $user = $items->first()->user;
            $employee = $user->employee;

            // Hitung semua status absensi
            $presentCount = $items->where('status', 'present')->count();
            $lateCount = $items->where('status', 'late')->count();
            $leaveCount = $items->where('status', 'leave')->count();
            $permitCount = $items->where('status', 'permit')->count();
            $sickCount = $items->where('status', 'sick')->count();
            $alphaCount = $items->where('status', 'alpha')->count();
            $offCount = $items->where('status', 'off')->count();

            // Resolve Daily Salary - first check Employee, then fall back to User for backward compatibility
            $workingDays = (int) Setting::getValue('attendance_working_days', 28);
            $employeeDailySalary = $employee?->daily_salary ?? 0;
            $employeeMonthlySalary = $employee?->monthly_salary ?? 0;
            
            $salaryCalculationNote = '';
            if ($employeeDailySalary > 0) {
                $dailySalary = $employeeDailySalary;
                $salaryCalculationNote = 'Gaji harian diisi manual';
            } elseif ($employeeMonthlySalary > 0) {
                $dailySalary = $employeeMonthlySalary / $workingDays;
                $salaryCalculationNote = 'Gaji harian dihitung dari gaji bulanan (' . number_format($employeeMonthlySalary, 0, ',', '.') . ' / ' . $workingDays . ' hari)';
            } else {
                $dailySalary = 0;
                $salaryCalculationNote = 'Gaji belum diatur';
            }
            
            // Get monthly salary from Employee
            $monthlySalary = $employee?->monthly_salary ?? 0;

            // Hitung total hari yang dibayar sesuai kebijakan
            $paidDays = $presentCount + $lateCount + $leaveCount + $permitCount + $sickCount;
            $unpaidDays = $alphaCount;
            
            // Hitung potongan keterlambatan (jika ada)
            $lateDeduction = 0;
            $totalLateMinutes = $items->where('status', 'late')->sum('late_minutes');
            $lateDeductionPerMinute = (int) Setting::getValue('attendance_late_deduction_per_minute', 0);
            if ($lateDeductionPerMinute > 0 && $totalLateMinutes > 0) {
                $lateDeduction = $totalLateMinutes * $lateDeductionPerMinute;
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
                ->filter(fn($a) => str_contains(strtolower($a->description ?? ''), 'warung') || str_contains(strtolower($a->description ?? ''), 'layanan cuci') || str_contains(strtolower($a->description ?? ''), 'kasbon karyawan'))
                ->sum('amount');
            
            // Kasbon Lainnya (Total Kasbon - Specific Kasbons)
            $kasbonLainnya = $totalKasbon - ($kasbonKantor + $kasbonWarung);

            $totalDailySalary = $paidDays * $dailySalary;
            $totalDeductions = $lateDeduction + $totalKasbon;
            $totalSalary = $totalDailySalary + $totalBonus - $totalDeductions;

            return [
                'user' => $user,
                'monthly_salary' => $monthlySalary,
                'present_count' => $presentCount,
                'late_count' => $lateCount,
                'total_late_minutes' => $totalLateMinutes,
                'leave_count' => $leaveCount,
                'permit_count' => $permitCount,
                'sick_count' => $sickCount,
                'alpha_count' => $alphaCount,
                'off_count' => $offCount,
                'paid_days' => $paidDays,
                'unpaid_days' => $unpaidDays,
                'working_days' => $workingDays,
                'daily_salary' => $dailySalary,
                'total_daily_salary' => $totalDailySalary,
                'late_deduction' => $lateDeduction,
                'total_deductions' => $totalDeductions,
                'salary_calculation_note' => $salaryCalculationNote,
                'bonus_disiplin' => $bonusDisiplin,
                'bonus_tanggung_jawab' => $bonusTanggungJawab,
                'bonus_absensi' => $bonusAbsensi,
                'bonus_lainnya' => $bonusLainnya,
                'total_bonus' => $totalBonus,
                'kasbon_kantor' => $kasbonKantor,
                'kasbon_warung' => $kasbonWarung,
                'kasbon_lainnya' => $kasbonLainnya,
                'total_kasbon' => $totalKasbon,
                'total_salary' => $totalSalary,
                'dates' => $items,
                'adjustments' => $userAdjustments,
            ];
        });
    }
}
