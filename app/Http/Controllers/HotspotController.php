<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Services\MikrotikService;
use Illuminate\Support\Facades\Auth;

class HotspotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $router = null;

        // If user is coordinator, get their assigned router
        if ($user->coordinator && $user->coordinator->router_id) {
            $router = Router::find($user->coordinator->router_id);
        } else {
            // Otherwise get the first active router
            $router = Router::where('is_active', true)->first();
        }

        $mikrotikConnected = false;
        $hotspotActiveSessions = [];

        if ($router) {
            $mikrotik = new MikrotikService($router);
            $mikrotikConnected = $mikrotik->isConnected();
            if ($mikrotikConnected) {
                $hotspotActiveSessions = $mikrotik->getHotspotActiveList();
            }
        }

        return view('hotspot.index', compact('router', 'mikrotikConnected', 'hotspotActiveSessions'));
    }
}
