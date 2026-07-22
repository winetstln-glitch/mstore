<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Modules\Network\Services\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PppoeController extends Controller
{
    public function __construct(protected MonitoringService $monitoringService) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $routers = Router::where('is_active', true)->get();
        $router = null;

        if ($user->coordinator && $user->coordinator->router_id && ! $user->hasRole('admin')) {
            $router = Router::find($user->coordinator->router_id);
        } elseif ($request->has('router_id')) {
            $router = Router::find($request->router_id);
        } else {
            $router = $routers->first();
        }

        $mikrotikConnected = false;
        $pppoeActiveSessions = [];

        if ($router) {
            $mikrotikConnected = $this->monitoringService->isRouterConnected($router);
            if ($mikrotikConnected) {
                $pppoeActiveSessions = $this->monitoringService->getPppoeActiveList($router);
            }
        }

        return view('pppoe.index', compact('router', 'routers', 'mikrotikConnected', 'pppoeActiveSessions'));
    }

    public function disconnect(Request $request, Router $router)
    {
        $request->validate([
            'id' => 'required|string',
        ]);

        $success = $this->monitoringService->disconnectPppoeById($router, $request->id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Sesi PPPoE berhasil diputus' : 'Gagal memutuskan sesi PPPoE',
        ]);
    }
}
