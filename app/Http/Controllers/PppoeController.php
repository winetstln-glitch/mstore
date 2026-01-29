<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Router;
use App\Services\MikrotikService;

class PppoeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Router::where('is_active', true);

        // Filter for Coordinator
        if (!$user->hasRole(['admin', 'management']) && $user->coordinator) {
            if ($user->coordinator->router_id) {
                $query->where('id', $user->coordinator->router_id);
            } elseif ($user->coordinator->region_id) {
                $query->where('region_id', $user->coordinator->region_id);
            } else {
                $query->where('id', 0); // No access
            }
        }

        $routers = $query->get();

        // Prioritize Router ID from request, or fallback to first available
        $routerId = $request->query('router_id');
        
        if ($routerId) {
            $router = $routers->where('id', $routerId)->first();
        } else {
            $router = $routers->first();
        }
        
        // If no valid router found (or requested router not allowed), show error or empty
        if (!$router) {
             // Handle case where no router is accessible
             return view('pppoe.index', [
                 'router' => null, 
                 'routers' => $routers,
                 'pppoeSecrets' => [], 
                 'pppoeProfiles' => [], 
                 'pppoeActive' => [], 
                 'mikrotikConnected' => false
             ]);
        }

        $pppoeSecrets = [];
        $pppoeProfiles = [];
        $pppoeActive = [];
        $mikrotikConnected = false;

        if ($router) {
            $mikrotik = new MikrotikService($router);
            if ($mikrotik->isConnected()) {
                $mikrotikConnected = true;
                $pppoeSecrets = $mikrotik->getSecrets();
                $pppoeProfiles = $mikrotik->getProfiles();
                $pppoeActive = $mikrotik->getPppoeActiveList();
            }
        }

        return view('pppoe.index', compact('router', 'routers', 'pppoeSecrets', 'pppoeProfiles', 'pppoeActive', 'mikrotikConnected'));
    }
}
