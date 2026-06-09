<?php

namespace App\Http\Controllers;

use App\Models\WeddingBooking;
use App\Models\WeddingPayment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class WeddingDashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wedding.view'),
        ];
    }

    public function __invoke(Request $request)
    {
        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $totalBooking = WeddingBooking::query()->count();
        $bookingThisMonth = WeddingBooking::query()
            ->whereBetween('event_date', [$startOfMonth, $endOfMonth])
            ->count();

        $revenue = WeddingPayment::query()
            ->where('status', 'paid')
            ->sum('amount');

        $eventsToday = WeddingBooking::query()
            ->whereDate('event_date', $today)
            ->count();

        $eventsThisWeek = WeddingBooking::query()
            ->whereBetween('event_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->count();

        $pendingPayments = WeddingPayment::query()
            ->where('status', 'pending')
            ->count();

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', event_date)"
            : "DATE_FORMAT(event_date, '%Y-%m')";

        $bookingByMonth = WeddingBooking::query()
            ->selectRaw($monthExpr.' as ym, COUNT(*) as total')
            ->groupBy('ym')
            ->orderBy('ym')
            ->limit(24)
            ->get()
            ->map(fn ($r) => ['ym' => (string) $r->ym, 'total' => (int) $r->total])
            ->all();

        $paymentMonthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', paid_at)"
            : "DATE_FORMAT(paid_at, '%Y-%m')";

        $revenueByMonth = WeddingPayment::query()
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->selectRaw($paymentMonthExpr.' as ym, SUM(amount) as total')
            ->groupBy('ym')
            ->orderBy('ym')
            ->limit(24)
            ->get()
            ->map(fn ($r) => ['ym' => (string) $r->ym, 'total' => (int) $r->total])
            ->all();

        return view('wedding.dashboard', [
            'stats' => [
                'total_booking' => $totalBooking,
                'booking_this_month' => $bookingThisMonth,
                'revenue' => (int) $revenue,
                'events_today' => $eventsToday,
                'events_this_week' => $eventsThisWeek,
                'pending_payments' => $pendingPayments,
            ],
            'charts' => [
                'booking_by_month' => $bookingByMonth,
                'revenue_by_month' => $revenueByMonth,
            ],
        ]);
    }
}

