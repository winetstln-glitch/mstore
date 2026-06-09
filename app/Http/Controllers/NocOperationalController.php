<?php

namespace App\Http\Controllers;

use App\Models\AreaOutage;
use App\Models\NetworkDiagnostic;
use App\Models\NetworkIncident;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class NocOperationalController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [];
    }

    public function areaOutage()
    {
        $items = AreaOutage::query()->latest('started_at')->paginate(30);

        return view('noc.operational.area_outage', [
            'items' => $items,
        ]);
    }

    public function incidents()
    {
        $items = NetworkIncident::query()->latest('started_at')->paginate(30);

        return view('noc.operational.network_incident', [
            'items' => $items,
        ]);
    }

    public function diagnostics()
    {
        $items = NetworkDiagnostic::query()->latest('created_at')->paginate(30);

        return view('noc.operational.network_diagnostic', [
            'items' => $items,
        ]);
    }

    public function diagnosticLogs()
    {
        $items = NetworkDiagnostic::query()->latest('created_at')->paginate(30);

        return view('noc.operational.diagnostic_logs', [
            'items' => $items,
        ]);
    }

    public function oltMonitoring()
    {
        return redirect()->route('olt.index');
    }

    public function fiberMonitoring()
    {
        return view('noc.operational.fiber_monitoring');
    }
}
