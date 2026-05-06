<?php

namespace App\Traits;

use App\Models\SalaryAdjustment;
use App\Models\TechnicianAttendance;
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
            $leaveCount = $items->whereIn('status', ['leave', 'permit', 'sick'])->count();

            $paidDays = $presentCount + $leaveCount;
            $dailySalary = $user->daily_salary > 0 ? $user->daily_salary : 0;

            $userAdjustments = $allAdjustments->get($user->id, collect());
            $totalBonus = $userAdjustments->where('type', 'bonus')->sum('amount');
            $totalKasbon = $userAdjustments->where('type', 'kasbon')->sum('amount');

            return [
                'user' => $user,
                'present_count' => $presentCount,
                'leave_count' => $leaveCount,
                'paid_days' => $paidDays,
                'daily_salary' => $dailySalary,
                'total_bonus' => $totalBonus,
                'total_kasbon' => $totalKasbon,
                'total_salary' => ($paidDays * $dailySalary) + $totalBonus - $totalKasbon,
                'dates' => $items,
                'adjustments' => $userAdjustments,
            ];
        });
    }
}
