<?php

namespace App\Http\Controllers;

use App\Models\TechnicianAttendance;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TechnicianAttendanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:attendance.view', only: ['index', 'exportPdf']),
        ];
    }

    /**
     * Display a listing of the resource (Admin Rekap).
     */
    public function index(Request $request)
    {
        $query = TechnicianAttendance::with('user');

        if ($request->filled('date')) {
            $query->whereDate('clock_in', $request->date);
        }
        
        if ($request->filled('month')) {
            $query->whereMonth('clock_in', date('m', strtotime($request->month)))
                  ->whereYear('clock_in', date('Y', strtotime($request->month)));
        }

        if (!Auth::user()->hasPermission('attendance.report') && !Auth::user()->hasRole('admin')) {
            $query->where('user_id', Auth::id());
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Calculate stats for the current filter
        $statsQuery = clone $query;
        $allAttendances = $statsQuery->get();
        
        $stats = [
            'present' => $allAttendances->where('status', 'present')->count(),
            'late' => $allAttendances->where('status', 'late')->count(),
            'leave' => $allAttendances->where('status', 'leave')->count(),
            'permit' => $allAttendances->where('status', 'permit')->count(),
            'sick' => $allAttendances->where('status', 'sick')->count(),
            'alpha' => $allAttendances->where('status', 'alpha')->count(),
            'total_days' => $allAttendances->count(),
        ];

        $attendances = $query->latest()->paginate(15);
        
        // List technicians and admins for filter
        $techniciansQuery = \App\Models\User::whereHas('role', function($q) {
            $q->whereIn('name', ['technician', 'admin']);
        });

        if (!Auth::user()->hasPermission('attendance.report') && !Auth::user()->hasRole('admin')) {
            $techniciansQuery->where('id', Auth::id());
        }

        $technicians = $techniciansQuery->get();

        return view('technicians.attendance.index', compact('attendances', 'technicians', 'stats'));
    }

    public function exportPdf(Request $request)
    {
        $query = TechnicianAttendance::with('user');

        if ($request->filled('date')) {
            $query->whereDate('clock_in', $request->date);
        }

        if ($request->filled('month')) {
            $query->whereMonth('clock_in', date('m', strtotime($request->month)))
                  ->whereYear('clock_in', date('Y', strtotime($request->month)));
        }

        if (!Auth::user()->hasPermission('attendance.report') && !Auth::user()->hasRole('admin')) {
            $query->where('user_id', Auth::id());
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $attendances = $query->oldest('clock_in')->get();

        // Summary by Technician
        $summary = $attendances->groupBy('user_id')->map(function ($items) {
            $user = $items->first()->user;
            
            // Calculate status counts
            $presentCount = $items->whereIn('status', ['present', 'late'])->count();
            $leaveCount = $items->whereIn('status', ['leave', 'permit', 'sick'])->count(); // Cuti/Izin/Sakit
            
            // Calculate salary (Present + Leave/Permit/Sick considered paid)
            // You can adjust which statuses are paid
            $paidDays = $presentCount + $leaveCount;
            $dailySalary = $user->daily_salary > 0 ? $user->daily_salary : 0;
            
            return [
                'user' => $user,
                'present_count' => $presentCount,
                'leave_count' => $leaveCount,
                'paid_days' => $paidDays,
                'daily_salary' => $dailySalary,
                'total_salary' => $paidDays * $dailySalary,
                'dates' => $items
            ];
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('technicians.attendance.pdf', compact('summary', 'request'));
        return $pdf->stream('rekap_teknisi_' . now()->format('Y-m-d_His') . '.pdf', ['Attachment' => false]);
    }

    public function exportExcel(Request $request)
    {
        $query = TechnicianAttendance::with('user');

        if ($request->filled('date')) {
            $query->whereDate('clock_in', $request->date);
        }

        if ($request->filled('month')) {
            $query->whereMonth('clock_in', date('m', strtotime($request->month)))
                  ->whereYear('clock_in', date('Y', strtotime($request->month)));
        }

        if (!Auth::user()->hasRole('admin')) {
            $query->where('user_id', Auth::id());
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $attendances = $query->oldest('clock_in')->get();

        // Summary by Technician
        $summary = $attendances->groupBy('user_id')->map(function ($items) {
            $user = $items->first()->user;
            
            // Calculate status counts
            $presentCount = $items->whereIn('status', ['present', 'late'])->count();
            $leaveCount = $items->whereIn('status', ['leave', 'permit', 'sick'])->count(); // Cuti/Izin/Sakit
            
            $paidDays = $presentCount + $leaveCount;
            $dailySalary = $user->daily_salary > 0 ? $user->daily_salary : 0;
            
            return [
                'user' => $user,
                'present_count' => $presentCount,
                'leave_count' => $leaveCount,
                'paid_days' => $paidDays,
                'daily_salary' => $dailySalary,
                'total_salary' => $paidDays * $dailySalary,
                'dates' => $items
            ];
        });

        return response()->streamDownload(function () use ($summary) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            // Sheet 1: Summary
            $writer->addRow(Row::fromValues(['REKAP GAJI TEKNISI']));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues([
                'Nama Teknisi', 
                'Total Hadir', 
                'Total Cuti/Izin/Sakit', 
                'Total Hari Dibayar', 
                'Gaji Harian', 
                'Total Gaji'
            ]));

            foreach ($summary as $data) {
                $writer->addRow(Row::fromValues([
                    $data['user']->name,
                    $data['present_count'],
                    $data['leave_count'],
                    $data['paid_days'],
                    $data['daily_salary'],
                    $data['total_salary']
                ]));
            }

            // Sheet 2: Details
            $writer->addNewSheetAndMakeItCurrent();
            $writer->addRow(Row::fromValues(['DETAIL ABSENSI']));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues([
                'Nama Teknisi',
                'Tanggal',
                'Jam Masuk',
                'Jam Pulang',
                'Status',
                'Catatan'
            ]));

            foreach ($summary as $data) {
                foreach ($data['dates'] as $attendance) {
                    $writer->addRow(Row::fromValues([
                        $data['user']->name,
                        $attendance->clock_in->translatedFormat('d F Y'),
                        $attendance->clock_in->format('H:i'),
                        $attendance->clock_out ? $attendance->clock_out->format('H:i') : '-',
                        __(ucfirst($attendance->status)),
                        $attendance->notes
                    ]));
                }
            }

            $writer->close();
        }, 'rekap_teknisi_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function recapToFinance(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $query = TechnicianAttendance::with('user');

        if ($request->filled('date')) {
            $query->whereDate('clock_in', $request->date);
        }

        if ($request->filled('month')) {
            $query->whereMonth('clock_in', date('m', strtotime($request->month)))
                  ->whereYear('clock_in', date('Y', strtotime($request->month)));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $attendances = $query->oldest('clock_in')->get();

        if ($attendances->isEmpty()) {
            return back()->with('error', __('No attendance records found for the selected period.'));
        }

        // Summary by Technician
        $summary = $attendances->groupBy('user_id')->map(function ($items) {
            $user = $items->first()->user;
            
            // Calculate status counts
            $presentCount = $items->whereIn('status', ['present', 'late'])->count();
            $leaveCount = $items->whereIn('status', ['leave', 'permit', 'sick'])->count();
            
            $paidDays = $presentCount + $leaveCount;
            $dailySalary = $user->daily_salary > 0 ? $user->daily_salary : 0;
            
            return [
                'total_salary' => $paidDays * $dailySalary,
            ];
        });

        $totalAmount = $summary->sum('total_salary');

        if ($totalAmount <= 0) {
            return back()->with('error', __('Total salary amount is zero. No transaction created.'));
        }

        // Create Description
        $period = '';
        if ($request->filled('month')) {
            $period = \Carbon\Carbon::parse($request->month)->translatedFormat('F Y');
        } elseif ($request->filled('date')) {
            $period = \Carbon\Carbon::parse($request->date)->translatedFormat('d F Y');
        } else {
            $period = __('All Time');
        }
        
        $description = "Pembayaran Gaji Teknisi Periode $period";
        if ($request->filled('user_id')) {
            $user = \App\Models\User::find($request->user_id);
            if ($user) {
                $description .= " - " . $user->name;
            }
        }

        Transaction::create([
            'user_id' => Auth::id(),
            'type' => 'expense',
            'category' => 'Salary',
            'amount' => $totalAmount,
            'description' => $description,
            'transaction_date' => now(),
        ]);

        return back()->with('success', __('Salary expense of :amount has been recorded in Finance.', ['amount' => number_format($totalAmount, 0, ',', '.')]));
    }

    public function sendNotification(TechnicianAttendance $attendance)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $user = $attendance->user;
        if (!$user || !$user->phone) {
            return back()->with('error', __('User does not have a phone number.'));
        }

        $clockIn = $attendance->clock_in->format('H:i');
        $clockOut = $attendance->clock_out ? $attendance->clock_out->format('H:i') : '-';
        $status = ucfirst($attendance->status);
        $date = $attendance->clock_in->translatedFormat('d F Y');

        $message = "Halo {$user->name},\n\nBerikut detail absensi Anda:\n📅 Tanggal: {$date}\n⏰ Masuk: {$clockIn}\n⏰ Pulang: {$clockOut}\n📊 Status: {$status}\n\nTerima kasih.";

        $wa = new WhatsAppService();
        $wa->sendMessage($user->phone, $message, 'attendance_notification');

        return back()->with('success', __('Notification sent via WhatsApp.'));
    }

    public function storeManual(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required|in:present,late,leave,permit,sick,alpha',
            'notes' => 'nullable|string',
        ]);

        // Check if exists
        $exists = TechnicianAttendance::where('user_id', $request->user_id)
            ->whereDate('clock_in', $request->date)
            ->exists();

        if ($exists) {
            return back()->with('error', __('Attendance record for this user on this date already exists.'));
        }

        TechnicianAttendance::create([
            'user_id' => $request->user_id,
            'clock_in' => $request->date . ' 08:00:00', // Default time
            'clock_out' => $request->date . ' 17:00:00',
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        return back()->with('success', __('Manual attendance added successfully.'));
    }

    /**
     * Show the form for creating a new resource (Technician Absen Page).
     */
    public function create()
    {
        $todayAttendance = TechnicianAttendance::where('user_id', Auth::id())
            ->whereDate('clock_in', today())
            ->first();

        $clockInStart = Setting::getValue('attendance_clock_in_start', '07:00');
        $clockInEnd = Setting::getValue('attendance_clock_in_end', '13:00');
        $clockOutStart = Setting::getValue('attendance_clock_out_start', '20:00');
        $clockOutEnd = Setting::getValue('attendance_clock_out_end', '01:00');
        $faceVerificationEnabled = Setting::getValue('attendance_face_verification', '0');

        return view('technicians.attendance.create', compact('todayAttendance', 'clockInStart', 'clockInEnd', 'clockOutStart', 'clockOutEnd', 'faceVerificationEnabled'));
    }

    /**
     * Store a newly created resource in storage (Clock In).
     */
    public function store(Request $request)
    {
        // Validation: Clock In Allowed based on Settings
        $clockInStart = Setting::getValue('attendance_clock_in_start', '07:00');
        $clockInEnd = Setting::getValue('attendance_clock_in_end', '13:00');
        
        $now = now();
        $currentTime = $now->format('H:i');

        if ($currentTime < $clockInStart || $currentTime > $clockInEnd) {
            return back()->withErrors(['message' => __('Clock In only allowed between :start - :end WIB.', ['start' => $clockInStart, 'end' => $clockInEnd])]);
        }

        $request->validate([
            'photo' => 'required|image|max:10240',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ]);

        // Radius Check
        $officeLat = Setting::getValue('attendance_office_lat');
        $officeLng = Setting::getValue('attendance_office_lng');
        $radius = Setting::getValue('attendance_radius', 100); // meters

        if ($officeLat && $officeLng && $request->latitude && $request->longitude) {
            $distance = $this->calculateDistance($request->latitude, $request->longitude, $officeLat, $officeLng);
            if ($distance > $radius) {
                return back()->withErrors(['message' => __('You are too far from the office. Distance: :dist m. Max: :max m.', ['dist' => round($distance), 'max' => $radius])]);
            }
        }

        $path = $request->file('photo')->store('attendance-photos', 'public');

        TechnicianAttendance::create([
            'user_id' => Auth::id(),
            'clock_in' => now(),
            'photo_clock_in' => $path,
            'lat_clock_in' => $request->latitude,
            'lng_clock_in' => $request->longitude,
            'status' => 'present',
            'notes' => $request->notes
        ]);

        return redirect()->route('attendance.create')->with('success', __('Clock In successful!'));
    }

    /**
     * Update the specified resource in storage (Clock Out).
     */
    public function update(Request $request, $id)
    {
        // Validation: Clock Out Allowed based on Settings
        $clockOutStart = Setting::getValue('attendance_clock_out_start', '20:00');
        $clockOutEnd = Setting::getValue('attendance_clock_out_end', '01:00');
        
        $now = now();
        $currentTime = $now->format('H:i');

        // Logic for overnight time range (e.g. 20:00 to 01:00)
        // If start > end, it means it crosses midnight.
        // Allowed if time >= start OR time <= end
        // Else (start < end), allowed if time >= start AND time <= end

        $isAllowed = false;
        if ($clockOutStart > $clockOutEnd) {
            // Crosses midnight
            $isAllowed = ($currentTime >= $clockOutStart || $currentTime <= $clockOutEnd);
        } else {
            // Same day
            $isAllowed = ($currentTime >= $clockOutStart && $currentTime <= $clockOutEnd);
        }

        if (! $isAllowed) {
             return back()->withErrors(['message' => __('Clock Out only allowed between :start - :end WIB.', ['start' => $clockOutStart, 'end' => $clockOutEnd])]);
        }

        $attendance = TechnicianAttendance::where('user_id', Auth::id())->findOrFail($id);
        
        $request->validate([
            'photo' => 'required|image|max:10240',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ]);

        // Radius Check
        $officeLat = Setting::getValue('attendance_office_lat');
        $officeLng = Setting::getValue('attendance_office_lng');
        $radius = Setting::getValue('attendance_radius', 100); // meters

        if ($officeLat && $officeLng && $request->latitude && $request->longitude) {
            $distance = $this->calculateDistance($request->latitude, $request->longitude, $officeLat, $officeLng);
            if ($distance > $radius) {
                return back()->withErrors(['message' => __('You are too far from the office. Distance: :dist m. Max: :max m.', ['dist' => round($distance), 'max' => $radius])]);
            }
        }

        $path = $request->file('photo')->store('attendance-photos', 'public');

        $attendance->update([
            'clock_out' => now(),
            'photo_clock_out' => $path,
            'lat_clock_out' => $request->latitude,
            'lng_clock_out' => $request->longitude,
            'notes' => $attendance->notes . "\nClock Out Note: " . $request->notes
        ]);

        return redirect()->route('attendance.create')->with('success', __('Clock Out successful!'));
    }

    public function destroy(TechnicianAttendance $attendance)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }
        if ($attendance->photo_clock_in) {
            Storage::disk('public')->delete($attendance->photo_clock_in);
        }
        if ($attendance->photo_clock_out) {
            Storage::disk('public')->delete($attendance->photo_clock_out);
        }
        $attendance->delete();
        return back()->with('success', __('Attendance record deleted.'));
    }

    public function bulkDestroy(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:technician_attendances,id',
        ]);

        $attendances = TechnicianAttendance::whereIn('id', $request->ids)->get();

        foreach ($attendances as $attendance) {
            if ($attendance->photo_clock_in) {
                Storage::disk('public')->delete($attendance->photo_clock_in);
            }
            if ($attendance->photo_clock_out) {
                Storage::disk('public')->delete($attendance->photo_clock_out);
            }
            $attendance->delete();
        }

        return back()->with('success', __('Selected attendance records deleted.'));
    }

    /**
     * Calculate distance between two points in meters using Haversine formula.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
