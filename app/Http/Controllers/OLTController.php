<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class OLTController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:olt.view', only: ['index', 'show']),
            new Middleware('permission:olt.create', only: ['create', 'store']),
            new Middleware('permission:olt.edit', only: ['edit', 'update']),
            new Middleware('permission:olt.delete', only: ['destroy']),
            new Middleware('permission:olt.test_connection', only: ['testConnection']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $olts = Olt::paginate(10);
        return view('olt.index', compact('olts'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Olt $olt)
    {
        // Calculate statistics from DB
        $totalOnus = $olt->onus()->count();
        $onlineOnus = $olt->onus()->where('status', 'online')->count();
        $offlineOnus = $olt->onus()->where('status', 'offline')->count();
        $losOnus = $olt->onus()->where('status', 'los')->count();
        
        // Signal distribution (Example logic)
        $goodSignal = $olt->onus()->where('signal', '>=', -25)->count();
        $warningSignal = $olt->onus()->whereBetween('signal', [-27, -25.1])->count();
        $badSignal = $olt->onus()->where('signal', '<', -27)->count();

        return view('olt.show', compact(
            'olt', 
            'totalOnus', 'onlineOnus', 'offlineOnus', 'losOnus',
            'goodSignal', 'warningSignal', 'badSignal'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('olt.create');
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
            'password' => 'required|string|max:255',
            'type' => 'required|string|in:epon,gpon,xpon',
            'brand' => 'required|string|in:zte,huawei,hsgq,vsol,cdata',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'snmp_port' => 'nullable|integer',
            'snmp_community' => 'nullable|string|max:255',
            'snmp_version' => 'nullable|string|max:10',
        ]);

        [$host, $port] = $this->normalizeHostPort($validated['host'], $validated['port']);
        $validated['host'] = $host;
        $validated['port'] = $port;

        Olt::create($validated);

        return redirect()->route('olt.index')->with('success', __('OLT created successfully.'));
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Olt $olt)
    {
        return view('olt.edit', compact('olt'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Olt $olt)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255', // Password optional on update
            'type' => 'required|string|in:epon,gpon,xpon',
            'brand' => 'required|string|in:zte,huawei,hsgq,vsol,cdata',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'snmp_port' => 'nullable|integer',
            'snmp_community' => 'nullable|string|max:255',
            'snmp_version' => 'nullable|string|max:10',
        ]);

        [$host, $port] = $this->normalizeHostPort($validated['host'], $validated['port']);
        $validated['host'] = $host;
        $validated['port'] = $port;

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $olt->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'id' => $olt->id]);
        }

        return redirect()->route('olt.index')->with('success', __('OLT updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Olt $olt)
    {
        $olt->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('olt.index')->with('success', __('OLT deleted successfully.'));
    }

    /**
     * Check OLT status (ping/port check).
     */
    public function checkStatus(Olt $olt)
    {
        $host = $olt->host;
        $port = $olt->port;
        $timeout = 2; // Fast check

        try {
            $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);

            if ($connection) {
                fclose($connection);
                return response()->json(['status' => 'online', 'message' => __('Online')]);
            } else {
                return response()->json(['status' => 'offline', 'message' => __('Offline')]);
            }
        } catch (\Throwable $e) {
            return response()->json(['status' => 'offline', 'message' => __('Error')]);
        }
    }

    /**
     * Get System Info from OLT (AJAX).
     */
    public function getSystemInfo(Olt $olt)
    {
        try {
            $service = new \App\Services\Olt\OltService();
            $driver = $service->getDriver($olt);
            $driver->connect($olt);
            $info = $driver->getSystemInfo();
            $driver->disconnect();
            
            return response()->json($info);
        } catch (\Throwable $e) {
            return response()->json([
                'uptime' => 'Error',
                'version' => 'Error',
                'temp' => 'Error',
                'cpu' => 'Error',
                'error' => $e->getMessage()
            ], 200); 
        }
    }

    /**
     * Test connection to OLT.
     */
    public function testConnection(Request $request)
    {
        set_time_limit(120); // Allow longer execution time

        $request->validate([
            'id' => 'nullable|integer|exists:olts,id',
            'host' => 'required_without:id',
            'port' => 'required_without:id',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'brand' => 'nullable|string',
        ]);

        // Scenario 1: Test existing OLT by ID (Preferred for Index Page)
        if ($request->filled('id')) {
            try {
                $olt = Olt::findOrFail($request->id);
                $service = new \App\Services\Olt\OltService();
                $result = $service->testLogin($olt, 20); // 20s timeout

                if ($result['success']) {
                    return response()->json(['success' => true, 'message' => __('Connection and Login successful!')]);
                } else {
                    \Illuminate\Support\Facades\Log::error("OLT Test Connection Failed (ID: {$olt->id}): " . $result['message']);
                    return response()->json(['success' => false, 'message' => __('Login failed: :message', ['message' => $result['message']])], 500);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("OLT Test Connection Error (ID: {$request->id}): " . $e->getMessage());
                return response()->json(['success' => false, 'message' => __('Connection error: :message', ['message' => $e->getMessage()])], 500);
            }
        }

        // Scenario 2: Test manually provided credentials (Preferred for Create/Edit Page)
        [$host, $port] = $this->normalizeHostPort($request->host, $request->port);

        if ($request->filled(['username', 'password', 'brand'])) {
            try {
                $service = new \App\Services\Olt\OltService();
                
                // Create a temporary OLT object
                $tempOlt = new Olt([
                    'host' => $host,
                    'port' => $port,
                    'username' => $request->username,
                    'password' => $request->password,
                    'brand' => $request->brand,
                ]);
                
                $result = $service->testLogin($tempOlt, 20); // 20s timeout
                
                if ($result['success']) {
                    return response()->json(['success' => true, 'message' => __('Connection and Login successful!')]);
                } else {
                    \Illuminate\Support\Facades\Log::error("OLT Manual Test Failed: " . $result['message']);
                    return response()->json(['success' => false, 'message' => __('Login failed: :message', ['message' => $result['message']])], 500);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("OLT Manual Test Error: " . $e->getMessage());
                return response()->json(['success' => false, 'message' => __('Connection error: :message', ['message' => $e->getMessage()])], 500);
            }
        }

        // Scenario 3: Fallback to simple port check (if no credentials provided)
        try {
            $connection = @fsockopen($host, $port, $errno, $errstr, 5);

            if ($connection) {
                fclose($connection);
                return response()->json(['success' => true, 'message' => __('Connection successful! Port is open. (Login not tested)')]);
            } else {
                return response()->json(['success' => false, 'message' => __('Connection failed: :message', ['message' => "$errstr ($errno)"])], 500);
            }
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => __('Connection error: :message', ['message' => $e->getMessage()])], 500);
        }
    }

    protected function normalizeHostPort($host, $port): array
    {
        $normalizedHost = trim((string) $host);
        $normalizedPort = $port;

        if (strpos($normalizedHost, ':') !== false) {
            $parts = explode(':', $normalizedHost);
            $maybePort = array_pop($parts);

            if (is_numeric($maybePort)) {
                $normalizedHost = implode(':', $parts);
                $normalizedPort = (int) $maybePort;
            }
        }

        return [$normalizedHost, $normalizedPort];
    }
}
