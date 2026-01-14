<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApiKey;
use App\Models\Olt;
use App\Models\Router; // Assuming Router model exists for Mikrotik
use App\Services\Olt\OltService;
use App\Services\MikrotikService;

class IntegrationController extends Controller
{
    protected $oltService;

    public function __construct(OltService $oltService)
    {
        $this->oltService = $oltService;
    }

    public function handle(Request $request)
    {
        $apiKeyStr = $request->query('api_key');
        $endpoint = $request->query('endpoint');

        if (!$apiKeyStr) {
            return response()->json(['error' => 'API Key required'], 401);
        }

        $apiKey = ApiKey::where('key', $apiKeyStr)->where('is_active', true)->first();

        if (!$apiKey) {
            return response()->json(['error' => 'Invalid or inactive API Key'], 401);
        }

        // Update usage
        $apiKey->update(['last_used_at' => now()]);

        switch ($endpoint) {
            case 'devices':
                return $this->getDevices();
            case 'olt/status':
                return $this->getOltStatus($request);
            case 'mikrotik/status':
                return $this->getMikrotikStatus($request);
            default:
                return response()->json(['error' => 'Invalid endpoint'], 400);
        }
    }

    protected function getDevices()
    {
        $olts = Olt::select('id', 'name', 'host', 'port', 'type', 'brand')->get();
        $mikrotiks = Router::select('id', 'name', 'host', 'port')->get();

        return response()->json([
            'olts' => $olts,
            'mikrotiks' => $mikrotiks,
        ]);
    }

    protected function getOltStatus(Request $request)
    {
        $deviceId = $request->query('device_id');
        $pon = $request->query('pon');

        if (!$deviceId) {
            return response()->json(['error' => 'device_id required'], 400);
        }

        $olt = Olt::find($deviceId);
        if (!$olt) {
            return response()->json(['error' => 'OLT not found'], 404);
        }

        try {
            $driver = $this->oltService->getDriver($olt);
            $driver->connect($olt);
            $onus = $driver->getOnus();
            $driver->disconnect();

            // Filter by PON if provided
            if ($pon) {
                // PON filtering logic:
                // User might send '1', '0/1', 'EPON0/1'. 
                // We'll do a loose match on the interface name.
                $onus = array_values(array_filter($onus, function ($onu) use ($pon) {
                    // Check if interface contains the pon string (e.g. "0/1" in "EPON0/1:1")
                    // Or if pon is just a number "1", match ":1" or "/1" ?
                    // Let's assume user sends a substantial part of the interface or just the port index.
                    // Simple contains check for now.
                    return str_contains($onu['interface'], $pon);
                }));
            }

            return response()->json([
                'device_id' => $olt->id,
                'name' => $olt->name,
                'total_onus' => count($onus),
                'data' => $onus
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function getMikrotikStatus(Request $request)
    {
        $deviceId = $request->query('device_id');

        if (!$deviceId) {
            return response()->json(['error' => 'device_id required'], 400);
        }

        $router = Router::find($deviceId);
        if (!$router) {
            return response()->json(['error' => 'Router not found'], 404);
        }

        try {
            $service = new MikrotikService($router);
            if (!$service->isConnected()) {
                return response()->json(['error' => 'Could not connect to Mikrotik'], 500);
            }

            $resource = $service->getSystemResource();
            $pppoeCount = $service->getPppoeActiveCount();
            $hotspotCount = $service->getHotspotActiveCount();

            return response()->json([
                'device_id' => $router->id,
                'name' => $router->name,
                'uptime' => $resource['uptime'] ?? 'N/A',
                'cpu_load' => ($resource['cpu-load'] ?? 0) . '%',
                'memory_usage' => $this->formatBytes(($resource['total-memory'] ?? 0) - ($resource['free-memory'] ?? 0)) . ' / ' . $this->formatBytes($resource['total-memory'] ?? 0),
                'pppoe_active' => $pppoeCount,
                'hotspot_active' => $hotspotCount,
                'version' => $resource['version'] ?? 'N/A',
                'board_name' => $resource['board-name'] ?? 'N/A',
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
