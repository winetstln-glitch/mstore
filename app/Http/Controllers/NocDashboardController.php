<?php

namespace App\Http\Controllers;

use App\Services\NocMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class NocDashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:noc.dashboard.view', only: ['index', 'data']),
        ];
    }

    public function index(Request $request, NocMetricsService $metrics)
    {
        $user = $request->user();
        $snapshot = $metrics->latestCached($user);

        return view('noc.dashboard', [
            'snapshot' => $snapshot,
            'scopeRegion' => $user?->coordinator?->region,
            'scopeCompany' => $user?->company,
        ]);
    }

    public function data(Request $request, NocMetricsService $metrics): JsonResponse
    {
        $user = $request->user();
        $snapshot = $metrics->latestCached($user);
        if (! $snapshot) {
            $snapshot = $metrics->computeForUser($user);
        }

        return response()->json([
            'ok' => true,
            'snapshot' => $snapshot,
        ]);
    }
}

