<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Modules\Network\Services\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class RouterController extends Controller implements HasMiddleware
{
    public function __construct(protected MonitoringService $monitoringService) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:router.view', only: ['index', 'show', 'testConnection']),
            new Middleware('permission:router.view|hotspot.view', only: ['sessions']),
            new Middleware('permission:router.create', only: ['create', 'store']),
            new Middleware('permission:router.edit', only: ['edit', 'update', 'disconnectPppoe', 'togglePppoeSecret', 'disconnectHotspot']),
            new Middleware('permission:router.delete', only: ['destroy']),
        ];
    }

    /**
     * Test connection to the router.
     */
    public function testConnection(Router $router)
    {
        try {
            $config = new \RouterOS\Config([
                'host' => $router->host,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => (int) $router->port,
                'timeout' => 5,
                'attempts' => 2,
            ]);

            $client = new \RouterOS\Client($config);

            // Try to get identity to verify connection
            $response = $client->query('/system/identity/print')->read();
            $identity = $response[0]['name'] ?? 'Unknown';

            return response()->json([
                'success' => true,
                'message' => __('Connected successfully! Router Identity: :identity', ['identity' => $identity]),
            ]);

        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'socket')) {
                $message .= ' (Check IP, Port, or if API service is enabled on Mikrotik)';
            }

            return response()->json([
                'success' => false,
                'message' => __('Connection failed: :message', ['message' => $message]),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        // If user is not admin and is a coordinator with an assigned router, redirect to details directly
        if (! $user->hasRole('admin') && $user->coordinator && $user->coordinator->router_id) {
            return redirect()->route('routers.sessions', $user->coordinator->router_id);
        }

        $routers = Router::latest()->paginate(10);

        return view('routers.index', compact('routers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('routers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer',
            'username' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'password' => 'required|string|max:255',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $router = Router::create($validated);

        try {
            app(\App\Services\VpnBridgeService::class)->provisionForRouter($router, auth()->user());
        } catch (\Throwable $e) {
        }

        return redirect()->route('routers.index')->with('success', __('Router added successfully.'));
    }

    public function show(Router $router)
    {
        $mikrotikConnected = $this->monitoringService->isRouterConnected($router);
        $systemResource = null;
        $pppoeActiveCount = 0;
        $hotspotActiveCount = 0;
        $memoryUsage = null;
        $memoryPercent = null;
        $pppoeSecrets = [];
        $pppoeProfiles = [];
        $pppoeActiveSessions = [];
        $hotspotActiveSessions = [];
        $interfacesTraffic = [];

        if ($mikrotikConnected) {
            $systemResource = $this->monitoringService->getSystemResource($router);
            $pppoeActiveCount = $this->monitoringService->getPppoeActiveCount($router);
            $hotspotActiveCount = $this->monitoringService->getHotspotActiveCount($router);
            $pppoeActiveSessions = $this->monitoringService->getPppoeActiveList($router);
            $hotspotActiveSessions = $this->monitoringService->getHotspotActiveList($router);

            $rawSecrets = $this->monitoringService->getSecrets($router);
            foreach ($rawSecrets as $secret) {
                if (is_array($secret) && array_key_exists('password', $secret)) {
                    unset($secret['password']);
                }
                $pppoeSecrets[] = $secret;
            }

            $pppoeProfiles = $this->monitoringService->getProfiles($router);

            if (is_array($systemResource)) {
                $totalMemory = isset($systemResource['total-memory']) ? (int) $systemResource['total-memory'] : 0;
                $freeMemory = isset($systemResource['free-memory']) ? (int) $systemResource['free-memory'] : 0;
                $usedMemory = max($totalMemory - $freeMemory, 0);

                if ($totalMemory > 0) {
                    $memoryUsage = $this->formatBytes($usedMemory).' / '.$this->formatBytes($totalMemory);
                    $memoryPercent = (int) round(($usedMemory / $totalMemory) * 100);
                }
            }

            $interfacesTraffic = $this->monitoringService->getInterfacesTrafficSnapshot($router, 4);
        }

        return view('routers.show', [
            'router' => $router,
            'mikrotikConnected' => $mikrotikConnected,
            'systemResource' => $systemResource,
            'pppoeActiveCount' => $pppoeActiveCount,
            'hotspotActiveCount' => $hotspotActiveCount,
            'memoryUsage' => $memoryUsage,
            'memoryPercent' => $memoryPercent,
            'pppoeSecrets' => $pppoeSecrets,
            'pppoeProfiles' => $pppoeProfiles,
            'pppoeActiveSessions' => $pppoeActiveSessions,
            'hotspotActiveSessions' => $hotspotActiveSessions,
            'interfacesTraffic' => $interfacesTraffic,
        ]);
    }

    public function sessions(Router $router)
    {
        $mikrotikConnected = $this->monitoringService->isRouterConnected($router);
        $pppoeActiveSessions = [];
        $hotspotActiveSessions = [];

        if ($mikrotikConnected) {
            $pppoeActiveSessions = $this->monitoringService->getPppoeActiveList($router);
            $hotspotActiveSessions = $this->monitoringService->getHotspotActiveList($router);
        }

        return view('routers.sessions', [
            'router' => $router,
            'mikrotikConnected' => $mikrotikConnected,
            'pppoeActiveSessions' => $pppoeActiveSessions,
            'hotspotActiveSessions' => $hotspotActiveSessions,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Router $router)
    {
        return view('routers.edit', compact('router'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Router $router)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer',
            'username' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'password' => 'nullable|string|max:255', // Optional on update
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $router->update($validated);

        return redirect()->route('routers.index')->with('success', __('Router updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Router $router)
    {
        $router->delete();

        return redirect()->route('routers.index')->with('success', __('Router deleted successfully.'));
    }

    public function disconnectPppoe(Request $request, Router $router)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        if (! $this->monitoringService->isRouterConnected($router)) {
            return response()->json([
                'success' => false,
                'message' => __('Router is offline or cannot connect to Mikrotik.'),
            ], 500);
        }

        if ($this->monitoringService->killActive($router, $data['name'])) {
            return response()->json([
                'success' => true,
                'message' => __('PPPoE session disconnected successfully.'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Failed to disconnect PPPoE session.'),
        ], 500);
    }

    public function togglePppoeSecret(Request $request, Router $router)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'enable' => 'required|boolean',
        ]);

        if (! $this->monitoringService->isRouterConnected($router)) {
            return response()->json([
                'success' => false,
                'message' => __('Router is offline or cannot connect to Mikrotik.'),
            ], 500);
        }

        if ($this->monitoringService->toggleSecret($router, $data['name'], $data['enable'])) {
            return response()->json([
                'success' => true,
                'message' => $data['enable']
                    ? __('PPPoE user has been unblocked.')
                    : __('PPPoE user has been blocked.'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Failed to update PPPoE user status.'),
        ], 500);
    }

    public function disconnectHotspot(Request $request, Router $router)
    {
        $data = $request->validate([
            'id' => 'required|string',
        ]);

        if (! $this->monitoringService->isRouterConnected($router)) {
            return response()->json([
                'success' => false,
                'message' => __('Router is offline or cannot connect to Mikrotik.'),
            ], 500);
        }

        if ($this->monitoringService->disconnectHotspotById($router, $data['id'])) {
            return response()->json([
                'success' => true,
                'message' => __('Hotspot session disconnected successfully.'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Failed to disconnect Hotspot session.'),
        ], 500);
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        if ($bytes <= 0) {
            return '0 B';
        }

        $pow = floor(log($bytes, 1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
