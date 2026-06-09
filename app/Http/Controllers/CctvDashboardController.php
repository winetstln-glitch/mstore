<?php

namespace App\Http\Controllers;

use App\Models\CctvBooking;
use App\Models\CctvPayment;
use App\Models\CctvSurvey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CctvDashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:cctv.view'),
        ];
    }

    public function __invoke(Request $request)
    {
        $pendingInstallations = CctvBooking::query()->whereIn('status', ['pending', 'scheduled', 'installation'])->count();
        $pendingSurveys = CctvSurvey::query()->where('status', 'pending')->count();
        $installationsThisMonth = CctvBooking::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $revenue = CctvPayment::query()->where('status', 'paid')->sum('amount');
        $activeTechnicians = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'technician'))
            ->where('is_active', true)
            ->count();

        return view('cctv.dashboard', [
            'stats' => [
                'pending_installations' => (int) $pendingInstallations,
                'pending_surveys' => (int) $pendingSurveys,
                'installations_this_month' => (int) $installationsThisMonth,
                'revenue' => (int) $revenue,
                'active_technicians' => (int) $activeTechnicians,
            ],
        ]);
    }
}

