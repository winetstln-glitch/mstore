<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Asset;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Htb;
use App\Models\Olt;
use App\Models\Region;
use App\Services\GenieACSService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MapController extends Controller implements HasMiddleware
{
    protected $genieService;

    public function __construct(GenieACSService $genieService)
    {
        $this->genieService = $genieService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:map.view', only: ['index']),
            new Middleware('permission:map.manage', except: ['index']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('finance');
        
        $coordinators = [];
        $regionId = null;

        if ($isAdmin) {
             $coordinators = \App\Models\Coordinator::with('region')->get();
        } else {
             $coordinator = \App\Models\Coordinator::where('user_id', $user->id)->first();
             if ($coordinator) {
                 $regionId = $coordinator->region_id;
             }
        }

        // Fetch OLTs
        $olts = Olt::all();

        // Fetch ODCs
        $odcQuery = Odc::query();
        if ($regionId) {
            $odcQuery->where('region_id', $regionId);
        }
        $odcs = $odcQuery->get();

        // Fetch ODPs
        $odpQuery = Odp::query();
        if ($regionId) {
            $odpQuery->where('region_id', $regionId);
        }
        $odps = $odpQuery->get();

        // Fetch HTBs
        $htbQuery = Htb::with(['parent', 'odp']);
        if ($regionId) {
            $htbQuery->whereHas('odp', function($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }
        $htbs = $htbQuery->get();

        // Fetch Regions
        $regions = Region::orderBy('name')->get();

        // Fetch Customers with coordinates
        $customerQuery = Customer::whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($regionId) {
            $customerQuery->whereHas('odp', function($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }

        $customers = $customerQuery->select(['id', 'name', 'address', 'latitude', 'longitude', 'status', 'phone', 'onu_serial', 'odp', 'odp_id', 'package'])
            ->with('odp:id,region_id')
            ->get();

        // Fetch GenieACS devices to get live status
        // We fetch a larger limit to cover active devices. In production, this should be paginated or optimized.
        $devices = $this->genieService->getDevices(300); 
        
        // Map GenieACS data to customers
        $genieData = [];
        foreach ($devices as $device) {
            $serial = $device['_deviceId']['_SerialNumber'] ?? null;
            $lastInform = $device['_lastInform'] ?? null;
            
            if ($serial) {
                // Extract RX Power
                // Handle different potential structures of the value (direct or object with _value)
                $rxPower = $device['VirtualParameters']['RXPower'] ?? null;
                if (is_array($rxPower) && isset($rxPower['_value'])) {
                    $rxPower = $rxPower['_value'];
                }
                
                // Extract PPPoE Username (as potential "Full Name" from GenieACS)
                $pppoeUser = $device['VirtualParameters']['pppoeUsername'] ?? null;
                 if (is_array($pppoeUser) && isset($pppoeUser['_value'])) {
                    $pppoeUser = $pppoeUser['_value'];
                }

                // Check online status
                $isOnline = false;
                if ($lastInform) {
                    $diff = now()->diffInSeconds(\Carbon\Carbon::parse($lastInform));
                    if ($diff < 300) {
                        $isOnline = true;
                    }
                }

                $genieData[$serial] = [
                    'is_online' => $isOnline,
                    'rx_power' => $rxPower,
                    'genie_name' => $pppoeUser
                ];
            }
        }

        // Attach online status to customers
        $customers->transform(function ($customer) use ($genieData) {
            if ($customer->onu_serial && isset($genieData[$customer->onu_serial])) {
                $customer->is_online = $genieData[$customer->onu_serial]['is_online'];
                $customer->rx_power = $genieData[$customer->onu_serial]['rx_power'];
                $customer->genie_name = $genieData[$customer->onu_serial]['genie_name'];
            } else {
                $customer->is_online = false;
                $customer->rx_power = null;
                $customer->genie_name = null;
            }
            return $customer;
        });

        // Fetch Assets (Tools) with location
        $assets = Asset::with(['item', 'holder'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        return view('map.index', compact('customers', 'odps', 'htbs', 'odcs', 'olts', 'regions', 'assets', 'coordinators', 'isAdmin'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Update the location of a resource.
     */
    public function updateLocation(Request $request, $type, $id)
    {
        $request->validate([
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ]);

        $model = null;
        switch ($type) {
            case 'olt': $model = Olt::find($id); break;
            case 'odc': $model = Odc::find($id); break;
            case 'odp': $model = Odp::find($id); break;
            case 'htb': $model = Htb::find($id); break;
            case 'customer': $model = Customer::find($id); break;
            case 'asset': $model = Asset::find($id); break;
        }

        if ($model) {
            $model->latitude = $request->latitude;
            $model->longitude = $request->longitude;
            $model->save();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Item not found'], 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
