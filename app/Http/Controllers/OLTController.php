<?php
// app/Http/Controllers/OLTController.php

namespace App\Http\Controllers;

use App\Models\Alarm;
use App\Models\Company;
use App\Models\OLT;
use App\Models\ONT;
use App\Models\OLTPort;
use App\Models\PollingLog;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Network\Services\MonitoringService;

class OLTController extends Controller
{
    public function __construct(protected MonitoringService $monitoringService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasAnyRole(config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']));

        $oltBase = OLT::query()->forUserArea($user);
        $ontBase = ONT::withoutTrashed()->has('olt')->forUserArea($user);

        if ($search = $request->get('search')) {
            $oltBase->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($regionId = $request->get('region_id')) {
            if ($isSuperAdmin) {
                $oltBase->where('region_id', $regionId);
            }
        }

        $olts = (clone $oltBase)->withCount('onts')->orderBy('name')->paginate(20)->withQueryString();

        $stats = [
            'total_olts' => (clone $oltBase)->count(),
            'online_olts' => (clone $oltBase)->where('status', 'online')->count(),
            'total_onts' => (clone $ontBase)->count(),
            'online_onts' => (clone $ontBase)->where('oper_status', 'online')->count(),
        ];

        $regions = $isSuperAdmin ? Region::orderBy('name')->get() : null;

        return view('olt.index', compact('olts', 'stats', 'regions', 'isSuperAdmin'))
            ->with('scopeRegion', $user?->coordinator?->region)
            ->with('scopeCompany', $user?->company);
    }

    public function create()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasAnyRole(config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']));

        $regions = Region::orderBy('name')->get();
        $companies = $isSuperAdmin ? Company::orderBy('name')->get() : null;

        return view('olt.form', compact('regions', 'companies', 'isSuperAdmin'))
            ->with('defaultCompanyId', $user->company_id)
            ->with('defaultRegionId', $user->coordinator?->region_id);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasAnyRole(config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']));

        $validated = $request->validate([
            'region_id' => $isSuperAdmin ? 'nullable|exists:regions,id' : 'nullable',
            'company_id' => $isSuperAdmin ? 'nullable|exists:companies,id' : 'nullable',
            'name' => 'required|string|max:100',
            'ip_address' => 'nullable|ip|unique:olts,ip_address,NULL,id,deleted_at,NULL',
            'vendor' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:200',
            'read_community' => 'nullable|string|max:100',
            'write_community' => 'nullable|string|max:100',
            'snmp_version' => 'in:v1,v2c,v3',
            'poll_interval' => 'integer|min:30|max:86400',
            'snmp_timeout' => 'integer|min:1|max:60',
            'snmp_retries' => 'integer|min:0|max:10',
            'is_active' => 'boolean',
            'host' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'nullable|string|max:100',
            'password' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:50',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['status'] = 'offline';
        $validated['company_id'] = $isSuperAdmin ? ($validated['company_id'] ?? $user->company_id) : $user->company_id;
        $validated['region_id'] = $isSuperAdmin ? ($validated['region_id'] ?? $user->coordinator?->region_id) : $user->coordinator?->region_id;

        if ((! isset($validated['ip_address']) || $validated['ip_address'] === null) && $request->filled('host')) {
            $validated['ip_address'] = $request->input('host');
        }
        if ((! isset($validated['vendor']) || $validated['vendor'] === null) && $request->filled('brand')) {
            $validated['vendor'] = $request->input('brand');
        }
        if ((! isset($validated['read_community']) || $validated['read_community'] === null) && $request->filled('snmp_community')) {
            $validated['read_community'] = $request->input('snmp_community');
        }

        $olt = OLT::create($validated);

        try {
            $driver = $this->monitoringService->getOltDriver($olt);
            $connected = $driver->testConnection();
            if ($connected) {
                $olt->update(['status' => 'online', 'last_online_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::error("Error testing connection after creating OLT: " . $e->getMessage());
        }

        return redirect()->route('olt.index')->with('success', 'OLT berhasil ditambahkan');
    }

    public function show(OLT $olt, Request $request)
    {
        $user = $request->user();
        $olt->load('ports');
        $onts = ONT::where('olt_id', $olt->id)
            ->forUserArea($user)
            ->with('port')
            ->orderBy('ont_id')
            ->get();
        $ports = $olt->ports;

        $allOlts = OLT::query()->forUserArea($user)->orderBy('name')->get();

        $stats = [
            'total_onts' => $onts->count(),
            'online_onts' => $onts->where('oper_status', 'online')->count(),
            'offline_onts' => $onts->where('oper_status', 'offline')->count(),
            'dying_gasp_onts' => $onts->where('oper_status', 'dying_gasp')->count(),
        ];

        $lastPoll = PollingLog::where('olt_id', $olt->id)->latest()->first();

        return view('olt.show', compact('olt', 'onts', 'ports', 'stats', 'lastPoll', 'allOlts'));
    }

    public function edit(OLT $olt)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasAnyRole(config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']));

        $regions = Region::orderBy('name')->get();
        $companies = $isSuperAdmin ? Company::orderBy('name')->get() : null;

        return view('olt.form', compact('olt', 'regions', 'companies', 'isSuperAdmin'))
            ->with('defaultCompanyId', $user->company_id)
            ->with('defaultRegionId', $user->coordinator?->region_id)
            ->with('scopeCompany', $user?->company);
    }

    public function update(Request $request, OLT $olt)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasAnyRole(config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']));

        $validated = $request->validate([
            'region_id' => $isSuperAdmin ? 'nullable|exists:regions,id' : 'nullable',
            'company_id' => $isSuperAdmin ? 'nullable|exists:companies,id' : 'nullable',
            'name' => 'required|string|max:100',
            'ip_address' => 'nullable|ip|unique:olts,ip_address,' . $olt->id . ',id,deleted_at,NULL',
            'vendor' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:200',
            'read_community' => 'nullable|string|max:100',
            'write_community' => 'nullable|string|max:100',
            'snmp_version' => 'in:v1,v2c,v3',
            'poll_interval' => 'integer|min:30|max:86400',
            'snmp_timeout' => 'integer|min:1|max:60',
            'snmp_retries' => 'integer|min:0|max:10',
            'is_active' => 'boolean',
            'host' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'nullable|string|max:100',
            'password' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:50',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        if ($isSuperAdmin) {
            if (array_key_exists('company_id', $validated) && $validated['company_id'] === null) {
                $validated['company_id'] = $olt->company_id ?? $user->company_id;
            }
        } else {
            unset($validated['company_id'], $validated['region_id']);
        }

        if ((! array_key_exists('ip_address', $validated) || $validated['ip_address'] === null) && $request->filled('host')) {
            $validated['ip_address'] = $request->input('host');
        }
        if ((! array_key_exists('vendor', $validated) || $validated['vendor'] === null) && $request->filled('brand')) {
            $validated['vendor'] = $request->input('brand');
        }
        if ((! array_key_exists('read_community', $validated) || $validated['read_community'] === null) && $request->filled('snmp_community')) {
            $validated['read_community'] = $request->input('snmp_community');
        }

        $olt->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('olt.index')->with('success', 'OLT berhasil diupdate');
    }

    /**
     * Remove the specified OLT.
     */
    public function destroy(OLT $olt)
    {
        $olt->onts()->delete();
        $olt->delete();
        
        return redirect()->route('olt.index')->with('success', 'OLT berhasil dihapus');
    }

    /**
     * Test SNMP connection to OLT (used in create/edit form).
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'vendor' => 'required|string|max:50',
            'read_community' => 'required|string|max:100',
        ]);

        try {
            $driver = $this->monitoringService->createOltDriverFromArray($request->all());
            $connected = $driver->testConnection();
            
            if ($connected) {
                $info = $driver->getDeviceInfo();
                return response()->json([
                    'connected' => true,
                    'info' => $info,
                ]);
            }
            
            return response()->json([
                'connected' => false,
                'message' => 'Tidak dapat terhubung ke OLT. Periksa IP dan community string.',
            ]);
        } catch (\Throwable $e) {
            Log::error("OLT test connection error: " . $e->getMessage());
            return response()->json([
                'connected' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Check OLT status (SNMP ping).
     */
    public function checkStatus(OLT $olt)
    {
        try {
            $driver = $this->monitoringService->getOltDriver($olt);
            $online = $driver->testConnection();
            
            $olt->update([
                'status' => $online ? 'online' : 'offline',
                'last_polled_at' => now(),
                'last_online_at' => $online ? now() : $olt->last_online_at,
            ]);
            
            return response()->json([
                'status' => $olt->status,
                'last_polled' => $olt->last_polled_at?->diffForHumans(),
            ]);
        } catch (\Throwable $e) {
            $olt->update(['status' => 'offline']);
            return response()->json(['status' => 'offline', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Batch check status of all active OLTs.
     */
    public function batchCheckStatus(Request $request)
    {
        $olts = OLT::query()->forUserArea($request->user())->where('is_active', true)->get();
        $results = [];
        
        foreach ($olts as $olt) {
            try {
                $driver = $this->monitoringService->getOltDriver($olt);
                $online = $driver->testConnection();
                $olt->update(['status' => $online ? 'online' : 'offline']);
                $results[] = [
                    'id' => $olt->id,
                    'name' => $olt->name,
                    'status' => $olt->status,
                ];
            } catch (\Throwable $e) {
                $olt->update(['status' => 'offline']);
                $results[] = [
                    'id' => $olt->id,
                    'name' => $olt->name,
                    'status' => 'offline',
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return response()->json([
            'checked' => count($results),
            'results' => $results,
        ]);
    }

    /**
     * Get system info from OLT.
     */
    public function systemInfo(OLT $olt)
    {
        try {
            $driver = $this->monitoringService->getOltDriver($olt);
            
            $info = $driver->getDeviceInfo();
            $resources = $driver->getSystemResources();
            $ports = $driver->getPorts();
            
            return response()->json([
                'success' => true,
                'device' => $info,
                'resources' => $resources,
                'ports' => $ports,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync ONUs from OLT via SNMP.
     */
    public function syncOnus(Request $request, OLT $olt)
    {
        $request->validate(['port' => 'nullable|string']);
        
        try {
            $result = $this->monitoringService->pollOlt($olt->id);
            
            if ($result['status'] === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => "Sinkronisasi berhasil. Ditemukan {$result['onts_found']} ONU.",
                    'onts_found' => $result['onts_found'],
                    'duration_ms' => $result['duration_ms'],
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Sinkronisasi gagal',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Filter ONUs by parameters.
     */
    public function filterOnus(Request $request, OLT $olt)
    {
        $query = ONT::where('olt_id', $olt->id)->with('port');
        
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ont_id', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }
        
        if ($status = $request->get('status')) {
            $query->where('oper_status', $status);
        }
        
        if ($portId = $request->get('port_id')) {
            $query->where('olt_port_id', $portId);
        }
        
        if ($vendor = $request->get('vendor')) {
            $query->where('vendor', $vendor);
        }
        
        $perPage = $request->get('per_page', 50);
        $onts = $query->orderBy('ont_id')->paginate($perPage);
        
        if ($request->wantsJson()) {
            return response()->json($onts);
        }
        
        return view('olt.onus.index', compact('olt', 'onts'));
    }

    /**
     * Update a single ONU.
     */
    public function updateOnu(Request $request, ONT $onu)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:200',
            'vendor' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:64',
            'mac_address' => 'nullable|string|max:17',
            'firmware_version' => 'nullable|string|max:50',
        ]);
        
        $onu->update($validated);
        
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'ONU berhasil diupdate']);
        }
        
        return back()->with('success', 'ONU berhasil diupdate');
    }

    /**
     * Delete a single ONU.
     */
    public function destroyOnu(Request $request, ONT $onu)
    {
        $onu->delete();
        
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'ONU berhasil dihapus']);
        }
        
        return back()->with('success', 'ONU berhasil dihapus');
    }

    /**
     * Poll a specific OLT (called from button).
     */
    public function poll(OLT $olt)
    {
        $result = $this->monitoringService->pollOlt($olt->id);
        
        if (request()->wantsJson()) {
            return response()->json($result);
        }
        
        if ($result['status'] === 'success') {
            return back()->with('success', "Polling berhasil. Ditemukan {$result['onts_found']} ONU.");
        }
        
        return back()->with('error', $result['error'] ?? 'Polling gagal');
    }
}
