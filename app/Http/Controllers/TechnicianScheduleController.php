<?php

namespace App\Http\Controllers;

use App\Models\TechnicianSchedule;
use App\Models\SchedulePeriod;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TechnicianScheduleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:schedule.view', only: ['index']),
            new Middleware('permission:schedule.manage', only: ['updatePeriod', 'store']),
        ];
    }

    public function index(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        
        // Get all technicians
        $techniciansQuery = User::whereHas('role', function($q) {
            $q->where('name', 'technician');
        });

        if (!Auth::user()->hasPermission('schedule.manage') && !Auth::user()->hasRole('admin')) {
            $techniciansQuery->where('id', Auth::id());
        }

        $technicians = $techniciansQuery->get();

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

        return view('schedules.index', compact('technicians', 'schedules', 'year', 'month', 'periods'));
    }

    public function updatePeriod(Request $request)
    {
        if (!Auth::user()->hasPermission('schedule.manage') && !Auth::user()->hasRole('admin')) {
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
        if (!Auth::user()->hasPermission('schedule.manage') && !Auth::user()->hasRole('admin')) {
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
}
