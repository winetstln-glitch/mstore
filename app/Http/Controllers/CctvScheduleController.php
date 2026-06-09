<?php

namespace App\Http\Controllers;

use App\Models\CctvBooking;
use App\Models\CctvInstallation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CctvScheduleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:cctv.view'),
        ];
    }

    public function index(Request $request)
    {
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        if ($request->filled('from')) {
            $from = now()->parse((string) $request->from)->startOfDay();
        }
        if ($request->filled('to')) {
            $to = now()->parse((string) $request->to)->endOfDay();
        }

        $installations = CctvInstallation::query()
            ->with(['booking.package', 'technician'])
            ->whereBetween('scheduled_at', [$from, $to])
            ->orderBy('scheduled_at')
            ->get();

        $unscheduledBookings = CctvBooking::query()
            ->with('package')
            ->whereNull('scheduled_at')
            ->whereIn('status', ['pending', 'survey', 'quotation', 'dp'])
            ->latest()
            ->limit(50)
            ->get();

        return view('cctv.schedule.index', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'installations' => $installations,
            'unscheduledBookings' => $unscheduledBookings,
        ]);
    }
}

