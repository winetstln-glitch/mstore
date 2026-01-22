<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LeaveRequestController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:leave.view', only: ['index']),
            new Middleware('permission:leave.create', only: ['store']),
            new Middleware('permission:leave.manage', only: ['update', 'destroy']), // Assuming these methods might exist or be added
        ];
    }

    public function index()
    {
        $user = Auth::user();
        $query = LeaveRequest::query()->with('user')->orderBy('created_at', 'desc');

        if (!$user->hasPermission('leave.manage') && !$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        if (request()->filled('reason_keyword')) {
            $kw = strtolower(request('reason_keyword'));
            $query->whereRaw('LOWER(reason) LIKE ?', ['%' . $kw . '%']);
        }

        $requests = $query->paginate(10)->withQueryString();
        
        // Calculate used quota for current month for the current user
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        $usedDays = 0;
        if (!$user->hasPermission('leave.manage') && !$user->hasRole('admin')) {
            $monthRequests = LeaveRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where(function($q) use ($startOfMonth, $endOfMonth) {
                    $q->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                      ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth]);
                })
                ->get();

            foreach ($monthRequests as $req) {
                // Calculate days overlap with current month
                $start = $req->start_date < $startOfMonth ? $startOfMonth : $req->start_date;
                $end = $req->end_date > $endOfMonth ? $endOfMonth : $req->end_date;
                
                if ($end >= $start) {
                    $usedDays += $start->diffInDays($end) + 1;
                }
            }
        }
        
        $quota = Setting::getValue('technician_leave_quota', 3);

        return view('leave_requests.index', compact('requests', 'usedDays', 'quota'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'category' => 'nullable|string|in:cuti,sakit,mendadak,keluarga,lainnya',
        ]);

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $daysRequested = $start->diffInDays($end) + 1;
        
        // Check quota (simplified: strictly check if total approved + requested <= quota)
        // In a real app, we might need more complex logic for cross-month leaves.
        $quota = (int) Setting::getValue('technician_leave_quota', 3);
        
        // Calculate already used days in the month of start_date
        $monthStart = $start->copy()->startOfMonth();
        $monthEnd = $start->copy()->endOfMonth();
        
        $usedDays = LeaveRequest::where('user_id', Auth::id())
            ->where('status', 'approved')
            ->where(function($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('start_date', [$monthStart, $monthEnd])
                  ->orWhereBetween('end_date', [$monthStart, $monthEnd]);
            })
            ->get()
            ->sum(function($req) use ($monthStart, $monthEnd) {
                $s = $req->start_date < $monthStart ? $monthStart : $req->start_date;
                $e = $req->end_date > $monthEnd ? $monthEnd : $req->end_date;
                return $s->diffInDays($e) + 1;
            });

        if (($usedDays + $daysRequested) > $quota) {
            return redirect()->back()->with('error', "Leave request exceeds monthly quota of $quota days. You have used $usedDays days.");
        }

        $reasonText = $request->reason;
        if ($request->filled('category')) {
            $labels = [
                'cuti' => 'Cuti',
                'sakit' => 'Sakit',
                'mendadak' => 'Keperluan Mendadak',
                'keluarga' => 'Keperluan Keluarga',
                'lainnya' => 'Izin Lainnya',
            ];
            $label = $labels[$request->category] ?? ucfirst($request->category);
            $reasonText = '[' . $label . '] ' . $reasonText;
        }

        $leave = LeaveRequest::create([
            'user_id' => Auth::id(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $reasonText,
            'status' => 'pending',
        ]);

        $reasonLower = strtolower($request->reason);
        if (str_contains($reasonLower, 'mendadak')) {
            $admins = \App\Models\User::whereHas('role', function($q){ $q->where('name', 'admin'); })->get();
            if ($admins->count() > 0) {
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\UrgentLeaveRequestNotification($leave));
                }
            }
        }

        return redirect()->back()->with('success', __('Leave request submitted successfully.'));
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        if (!Auth::user()->hasPermission('leave.manage')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
        ]);

        $leaveRequest->update([
            'status' => $request->status,
            'approved_by' => Auth::id(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->back()->with('success', __('Leave request updated successfully.'));
    }
}
