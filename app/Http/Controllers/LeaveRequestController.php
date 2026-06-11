<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\User;
use App\Traits\SendsNotifications;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveRequestController extends Controller implements HasMiddleware
{
    use SendsNotifications;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:leave.view', only: ['employee']),
            new Middleware('permission:leave.create', only: ['store']),
            new Middleware('permission:leave.manage', only: ['admin', 'update']),
            new Middleware('permission:leave.edit', only: ['edit', 'updateRequest']),
        ];
    }

    public function edit(LeaveRequest $leaveRequest)
    {
        // Only owner can edit pending request, or admin
        if (! Auth::user()->hasPermission('leave.manage') && ($leaveRequest->user_id !== Auth::id() || $leaveRequest->status !== 'pending')) {
            abort(403, 'Unauthorized');
        }

        return view('leave_requests.edit', compact('leaveRequest'));
    }

    public function updateRequest(Request $request, LeaveRequest $leaveRequest)
    {
        if (! Auth::user()->hasPermission('leave.manage') && ($leaveRequest->user_id !== Auth::id() || $leaveRequest->status !== 'pending')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'category' => 'nullable|string|in:cuti,sakit,mendadak,keluarga,lainnya',
        ]);

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $daysRequested = $start->diffInDays($end) + 1;

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
            $reasonText = '['.$label.'] '.$reasonText;
        }

        $type = 'leave';
        if ($request->filled('category')) {
            if ($request->category === 'sakit') {
                $type = 'sick';
            } elseif (in_array($request->category, ['mendadak', 'keluarga', 'lainnya'])) {
                $type = 'permission';
            }
        }

        $leaveRequest->update([
            'type' => $type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $reasonText,
            'leave_days_used' => $daysRequested,
        ]);

        return redirect()->route(Auth::user()->hasPermission('leave.manage') ? 'admin.leave-requests' : 'employee.leave-requests')
            ->with('success', __('Leave request updated successfully.'));
    }

    public function employee()
    {
        $user = Auth::user();
        $query = LeaveRequest::query()->where('user_id', $user->id)->orderBy('created_at', 'desc');

        if (request()->filled('reason_keyword')) {
            $kw = strtolower(request('reason_keyword'));
            $query->whereRaw('LOWER(reason) LIKE ?', ['%'.$kw.'%']);
        }

        $requests = $query->paginate(10)->withQueryString();

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthRequests = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth]);
            })
            ->get();

        $usedDays = 0;
        foreach ($monthRequests as $req) {
            $start = $req->start_date < $startOfMonth ? $startOfMonth : $req->start_date;
            $end = $req->end_date > $endOfMonth ? $endOfMonth : $req->end_date;

            if ($end >= $start) {
                $usedDays += $start->diffInDays($end) + 1;
            }
        }

        $quota = Setting::getValue('technician_leave_quota', 3);

        return view('leave_requests.my-leave', compact('requests', 'usedDays', 'quota'));
    }

    public function admin()
    {
        $query = LeaveRequest::query()->with('user')->orderBy('created_at', 'desc');

        if (request()->filled('reason_keyword')) {
            $kw = strtolower(request('reason_keyword'));
            $query->whereRaw('LOWER(reason) LIKE ?', ['%'.$kw.'%']);
        }

        $requests = $query->paginate(10)->withQueryString();

        return view('leave_requests.manage-leave', compact('requests'));
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
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('start_date', [$monthStart, $monthEnd])
                    ->orWhereBetween('end_date', [$monthStart, $monthEnd]);
            })
            ->get()
            ->sum(function ($req) use ($monthStart, $monthEnd) {
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
            $reasonText = '['.$label.'] '.$reasonText;
        }

        // Determine type based on category or reason
        $type = 'leave';
        if ($request->filled('category')) {
            if ($request->category === 'sakit') {
                $type = 'sick';
            } elseif (in_array($request->category, ['mendadak', 'keluarga', 'lainnya'])) {
                $type = 'permission';
            }
        }

        $leave = LeaveRequest::create([
            'user_id' => Auth::id(),
            'type' => $type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $reasonText,
            'leave_days_used' => $daysRequested,
            'status' => 'pending',
        ]);

        // Notify Group via WhatsApp & Telegram
        $user = Auth::user();
        $startDate = $leave->start_date->translatedFormat('d M Y');
        $endDate = $leave->end_date->translatedFormat('d M Y');
        $days = $start->diffInDays($end) + 1;
        
        $waMessage = "📝 *PENGAJUAN IZIN/CUTI BARU*\n\n" .
                     "👤 *Nama:* {$user->name}\n" .
                     "📅 *Dari:* {$startDate}\n" .
                     "📅 *Sampai:* {$endDate} ({$days} hari)\n" .
                     "📝 *Alasan:* {$leave->reason}\n" .
                     "📊 *Status:* MENUNGGU PERSETUJUAN ⏳\n\n" .
                     "🚀 _Sistem M-Store_";
        
        $this->sendGroupNotification($waMessage, 'attendance');

        $reasonLower = strtolower($request->reason);
        if (str_contains($reasonLower, 'mendadak')) {
            $admins = \App\Models\User::whereHas('role', function ($q) {
                $q->where('name', 'admin');
            })->get();
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
        if (! Auth::user()->hasPermission('leave.manage')) {
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

        $start = $leaveRequest->start_date;
        $end = $leaveRequest->end_date;

        // Handle APPROVED or REJECTED status
        if ($leaveRequest->status === 'approved') {
            // Determine attendance status based on leave type
            $attendanceStatus = match($leaveRequest->type) {
                'sick' => 'sick',
                'permission' => 'permit',
                default => 'leave',
            };

            // Loop through each date from start to end
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                // Check if attendance already exists for this user and date
                $exists = \App\Models\TechnicianAttendance::where('user_id', $leaveRequest->user_id)
                    ->where(function ($q) use ($date) {
                        $q->whereDate('clock_in', $date->toDateString())
                          ->orWhereDate('work_date', $date->toDateString());
                    })
                    ->exists();

                if (! $exists) {
                    // Create attendance entry
                    \App\Models\TechnicianAttendance::create([
                        'user_id' => $leaveRequest->user_id,
                        'work_date' => $date->toDateString(),
                        'clock_in' => $date->toDateString() . ' 08:00:00',
                        'clock_out' => $date->toDateString() . ' 17:00:00',
                        'status' => $attendanceStatus,
                        'notes' => ucfirst($attendanceStatus) . ' otomatis dari pengajuan cuti #' . $leaveRequest->id,
                        'generated_type' => 'leave_request',
                    ]);
                }
            }
        } elseif ($leaveRequest->status === 'rejected') {
            // Loop through each date from start to end
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                // Check if attendance already exists for this user and date
                $exists = \App\Models\TechnicianAttendance::where('user_id', $leaveRequest->user_id)
                    ->where(function ($q) use ($date) {
                        $q->whereDate('clock_in', $date->toDateString())
                          ->orWhereDate('work_date', $date->toDateString());
                    })
                    ->exists();

                if (! $exists) {
                    // Create alpha attendance entry
                    \App\Models\TechnicianAttendance::create([
                        'user_id' => $leaveRequest->user_id,
                        'work_date' => $date->toDateString(),
                        'clock_in' => $date->toDateString() . ' 08:00:00',
                        'clock_out' => $date->toDateString() . ' 17:00:00',
                        'status' => 'alpha',
                        'notes' => 'Alpha otomatis karena pengajuan cuti #' . $leaveRequest->id . ' ditolak',
                        'generated_type' => 'leave_request_rejected',
                    ]);
                }
            }
        }

        // Notify Group via WhatsApp & Telegram
        $user = $leaveRequest->user;
        $statusLabel = $leaveRequest->status === 'approved' ? 'DISETUJUI ✅' : 'DITOLAK ❌';
        $dateRange = $leaveRequest->start_date->translatedFormat('d M Y') . ' s/d ' . $leaveRequest->end_date->translatedFormat('d M Y');
        
        $waMessage = "📢 *UPDATE STATUS IZIN/CUTI*\n\n" .
                     "👤 *Nama:* {$user->name}\n" .
                     "📅 *Periode:* {$dateRange}\n" .
                     "📊 *Status:* {$statusLabel}\n" .
                     ($leaveRequest->status === 'rejected' ? "📝 *Alasan Penolakan:* {$leaveRequest->rejection_reason}\n" : "") .
                     "👮 *Oleh:* " . Auth::user()->name . "\n\n" .
                     "🚀 _Sistem M-Store_";
        
        $this->sendGroupNotification($waMessage, 'attendance');

        return redirect()->back()->with('success', __('Leave request updated successfully.'));
    }
}
