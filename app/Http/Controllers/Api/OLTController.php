<?php
// app/Http/Controllers/Api/OLTController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OLT;
use App\Models\ONT;
use App\Models\Alarm;
use App\Services\OLT\OLTPollService;
use App\Services\OLT\OLTFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OLTController extends Controller
{
    public function index()
    {
        $olts = OLT::withCount(['onts', 'alarms' => function($q) {
            $q->where('resolved', false);
        }])->get();
        
        return response()->json([
            'data' => $olts->map(function($olt) {
                return [
                    'id' => $olt->id,
                    'name' => $olt->name,
                    'ip' => $olt->ip_address,
                    'vendor' => $olt->vendor,
                    'model' => $olt->model,
                    'location' => $olt->location,
                    'status' => $olt->status,
                    'cpu' => $olt->cpu_usage,
                    'memory' => $olt->memory_usage,
                    'temperature' => $olt->temperature,
                    'uptime' => $olt->uptime,
                    'ont_count' => $olt->onts_count,
                    'active_alarms' => $olt->alarms_count,
                    'last_polled' => $olt->last_polled_at?->diffForHumans(),
                ];
            }),
        ]);
    }

    public function show(int $id)
    {
        $olt = OLT::with([
            'ports',
            'onts' => function($q) {
                $q->where('oper_status', 'online');
            },
        ])->findOrFail($id);
        
        $stats = [
            'total_onts' => $olt->onts()->count(),
            'online_onts' => $olt->onts()->where('oper_status', 'online')->count(),
            'offline_onts' => $olt->onts()->where('oper_status', 'offline')->count(),
            'dying_gasp_onts' => $olt->onts()->where('oper_status', 'dying_gasp')->count(),
        ];
        
        return response()->json([
            'olt' => $olt,
            'stats' => $stats,
            'ports' => $olt->ports,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'ip_address' => 'required|ip',
            'vendor' => 'required|string|max:50',
            'read_community' => 'required|string|max:100',
            'write_community' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:200',
            'snmp_version' => 'in:v1,v2c,v3',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $olt = OLT::create($validator->validated());
        
        return response()->json(['data' => $olt, 'message' => 'OLT created successfully'], 201);
    }

    public function poll(int $id, OLTPollService $pollService)
    {
        $result = $pollService->poll($id);
        
        return response()->json([
            'message' => $result['status'] === 'success' ? 'Polling successful' : 'Polling failed',
            'result' => $result,
        ]);
    }

    public function ontList(int $oltId, Request $request)
    {
        $olt = OLT::findOrFail($oltId);
        
        $query = ONT::where('olt_id', $oltId)
            ->with('port');
        
        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('oper_status', $status);
        }
        
        // Filter by port
        if ($portId = $request->get('port_id')) {
            $query->where('olt_port_id', $portId);
        }
        
        // Search
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ont_id', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%");
            });
        }
        
        $perPage = $request->get('per_page', 50);
        $onts = $query->orderBy('name')->paginate($perPage);
        
        return response()->json($onts);
    }

    public function ontShow(int $oltId, int $ontId)
    {
        $ont = ONT::where('olt_id', $oltId)
            ->with(['port', 'olt'])
            ->findOrFail($ontId);
        
        $history = [
            'optical' => $ont->opticalHistory()->latest()->take(24)->get(),
            'traffic' => $ont->trafficHistory()->latest()->take(24)->get(),
            'alarms' => $ont->alarms()->latest()->take(20)->get(),
        ];
        
        return response()->json([
            'ont' => $ont,
            'history' => $history,
        ]);
    }

    public function dashboard()
    {
        $olts = OLT::where('is_active', true)->get();
        
        $totalOnts = 0;
        $onlineOnts = 0;
        $offlineOnts = 0;
        $activeAlarms = 0;
        
        foreach ($olts as $olt) {
            $totalOnts += $olt->onts()->count();
            $onlineOnts += $olt->onts()->where('oper_status', 'online')->count();
            $offlineOnts += $olt->onts()->where('oper_status', 'offline')->count();
            $activeAlarms += $olt->alarms()->where('resolved', false)->count();
        }
        
        return response()->json([
            'olts' => [
                'total' => $olts->count(),
                'online' => $olts->where('status', 'online')->count(),
                'offline' => $olts->where('status', 'offline')->count(),
            ],
            'onts' => [
                'total' => $totalOnts,
                'online' => $onlineOnts,
                'offline' => $offlineOnts,
            ],
            'alarms' => [
                'active' => $activeAlarms,
            ],
            'vendor_distribution' => ONT::selectRaw('vendor, count(*) as total')
                ->groupBy('vendor')
                ->get()
                ->pluck('total', 'vendor'),
            'latest_alarms' => Alarm::with('olt')
                ->where('resolved', false)
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }

    public function connectTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|ip',
            'vendor' => 'required|string|max:50',
            'read_community' => 'required|string|max:100',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $factory = app(OLTFactory::class);
            $driver = $factory->createFromArray($request->all());
            
            $connected = $driver->testConnection();
            
            if ($connected) {
                $info = $driver->getDeviceInfo();
                return response()->json([
                    'connected' => true,
                    'info' => $info,
                ]);
            }
            
            return response()->json(['connected' => false, 'message' => 'SNMP connection failed'], 400);
            
        } catch (\Throwable $e) {
            return response()->json(['connected' => false, 'message' => $e->getMessage()], 400);
        }
    }
}