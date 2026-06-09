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

    public function index(NocMetricsService $metrics)
    {
        $snapshot = $metrics->latestCached();

        return view('noc.dashboard', [
            'snapshot' => $snapshot,
        ]);
    }

    public function data(Request $request, NocMetricsService $metrics): JsonResponse
    {
        $snapshot = $metrics->latestCached();
        if (! $snapshot) {
            $snapshot = $metrics->capture();
        }

        return response()->json([
            'ok' => true,
            'snapshot' => $snapshot,
        ]);
    }
}

