<?php

namespace App\Http\Controllers;

use App\Models\WeddingBooking;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WeddingScheduleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wedding.view'),
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

        $events = WeddingBooking::query()
            ->with('package')
            ->whereBetween('event_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('event_date')
            ->get();

        return view('wedding.schedule.index', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'events' => $events,
        ]);
    }
}

