<?php

namespace App\Http\Controllers;

use App\Models\AreaOutage;
use App\Models\NetworkDiagnostic;
use App\Models\NetworkIncident;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class NocOperationalController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [];
    }

    public function areaOutage(Request $request)
    {
        $user = $request->user();
        $query = AreaOutage::query()->forUserArea($user);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $items = $query->latest('started_at')->paginate(30)->withQueryString();

        return view('noc.operational.area_outage', [
            'items' => $items,
            'scopeRegion' => $user?->coordinator?->region,
            'scopeCompany' => $user?->company,
        ]);
    }

    public function incidents(Request $request)
    {
        $user = $request->user();
        $query = NetworkIncident::query()->forUserArea($user);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $items = $query->latest('started_at')->paginate(30)->withQueryString();

        return view('noc.operational.network_incident', [
            'items' => $items,
            'scopeRegion' => $user?->coordinator?->region,
            'scopeCompany' => $user?->company,
        ]);
    }

    public function diagnostics(Request $request)
    {
        $user = $request->user();
        $query = NetworkDiagnostic::query()->forUserArea($user);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $items = $query->latest('created_at')->paginate(30)->withQueryString();

        return view('noc.operational.network_diagnostic', [
            'items' => $items,
            'scopeRegion' => $user?->coordinator?->region,
            'scopeCompany' => $user?->company,
        ]);
    }

    public function diagnosticLogs(Request $request)
    {
        $user = $request->user();
        $query = NetworkDiagnostic::query()->forUserArea($user);

        $items = $query->latest('created_at')->paginate(30)->withQueryString();

        return view('noc.operational.diagnostic_logs', [
            'items' => $items,
            'scopeRegion' => $user?->coordinator?->region,
            'scopeCompany' => $user?->company,
        ]);
    }

    public function oltMonitoring()
    {
        return redirect()->route('olt.index');
    }

    public function fiberMonitoring()
    {
        $user = auth()->user();

        return view('noc.operational.fiber_monitoring', [
            'scopeRegion' => $user?->coordinator?->region,
            'scopeCompany' => $user?->company,
        ]);
    }
}
