<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\SlaMonitoringService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SlaMonitoringController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:sla.monitoring.view', only: ['index', 'data']),
        ];
    }

    public function index(SlaMonitoringService $service)
    {
        $from = now()->subDays(30)->startOfDay();
        $to = now()->endOfDay();
        $summary = $service->slaDashboardSummary($from, $to);

        $critical = Ticket::query()
            ->with('customer:id,name')
            ->whereNotIn('status', ['closed', 'solved'])
            ->whereIn('sla_status', ['warning', 'critical', 'breached'])
            ->orderByRaw("CASE sla_status WHEN 'breached' THEN 1 WHEN 'critical' THEN 2 WHEN 'warning' THEN 3 ELSE 4 END")
            ->latest('created_at')
            ->limit(50)
            ->get();

        return view('sla.monitoring', [
            'summary' => $summary,
            'tickets' => $critical,
        ]);
    }

    public function data(Request $request, SlaMonitoringService $service): JsonResponse
    {
        $from = $this->parseDate($request->query('from'), now()->subDays(30)->startOfDay());
        $to = $this->parseDate($request->query('to'), now()->endOfDay());

        return response()->json([
            'ok' => true,
            'summary' => $service->slaDashboardSummary($from, $to),
        ]);
    }

    private function parseDate(?string $value, Carbon $fallback): Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return $fallback;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
