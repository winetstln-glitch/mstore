<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Odc;
use App\Models\Odp;
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
        // Fetch OLTs
        $olts = Olt::all();

        // Fetch ODCs
        $odcs = Odc::all();

        // Fetch ODPs
        $odps = Odp::all();

        // Fetch Regions
        $regions = Region::orderBy('name')->get();

        // Fetch Customers with coordinates
        $customers = Customer::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'name', 'address', 'latitude', 'longitude', 'status', 'phone', 'onu_serial', 'odp', 'odp_id']);

        // Fetch GenieACS devices to get live status
        // We fetch a larger limit to cover active devices. In production, this should be paginated or optimized.
        $devices = $this->genieService->getDevices(300); 
        
        // Map GenieACS data to customers
        $onlineSerials = [];
        foreach ($devices as $device) {
            $serial = $device['_deviceId']['_SerialNumber'] ?? null;
            $lastInform = $device['_lastInform'] ?? null;
            
            if ($serial && $lastInform) {
                // Consider online if lastInform is within 5 minutes (300 seconds)
                // Note: Timezone handling might be needed. GenieACS returns ISO8601.
                $diff = now()->diffInSeconds(\Carbon\Carbon::parse($lastInform));
                if ($diff < 300) {
                    $onlineSerials[] = $serial;
                }
            }
        }

        // Attach online status to customers
        $customers->transform(function ($customer) use ($onlineSerials) {
            if ($customer->onu_serial && in_array($customer->onu_serial, $onlineSerials)) {
                $customer->is_online = true;
            } else {
                $customer->is_online = false;
            }
            return $customer;
        });

        return view('map.index', compact('customers', 'odps', 'odcs', 'olts', 'regions'));
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
