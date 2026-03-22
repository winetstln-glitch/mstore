<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Closure;
use App\Models\Customer;
use App\Models\GenieDeviceStatus;
use App\Models\Htb;
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
            $htbQuery->whereHas('odp', function ($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }
        $htbs = $htbQuery->get();

        // Fetch Closures
        $closureQuery = Closure::query();
        if ($regionId) {
            $closureQuery->where('region_id', $regionId);
        }
        $closures = $closureQuery->get();

        // Fetch Regions
        $regions = Region::orderBy('name')->get();

        // Fetch Customers with coordinates
        $customerQuery = Customer::whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($regionId) {
            $customerQuery->whereHas('odp', function ($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }

        $customers = $customerQuery->select(['id', 'name', 'address', 'latitude', 'longitude', 'status', 'phone', 'onu_serial', 'odp', 'odp_id', 'package', 'path'])
            ->with('odp:id,region_id')
            ->get();

        $customerIds = $customers->pluck('id')->all();
        $serials = $customers->pluck('onu_serial')->filter()->values()->all();
        $statusesByCustomer = GenieDeviceStatus::query()
            ->whereIn('customer_id', $customerIds)
            ->get()
            ->keyBy('customer_id');
        $statusesBySerial = GenieDeviceStatus::query()
            ->whereNull('customer_id')
            ->whereIn('onu_serial', $serials)
            ->get()
            ->keyBy('onu_serial');

        // Attach status terintegrasi dari tabel monitoring GenieACS
        $customers->transform(function ($customer) use ($statusesByCustomer, $statusesBySerial) {
            $status = $statusesByCustomer->get($customer->id);
            if (! $status && $customer->onu_serial) {
                $status = $statusesBySerial->get($customer->onu_serial);
            }

            if ($status) {
                $customer->is_online = (bool) $status->is_online;
                $customer->tr069_ip = $status->tr069_ip;
                $customer->rx_power = null;
                $customer->genie_name = null;
            } else {
                $customer->is_online = false;
                $customer->tr069_ip = null;
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

        return view('map.index', compact('customers', 'odps', 'htbs', 'odcs', 'olts', 'regions', 'assets', 'coordinators', 'isAdmin', 'closures'));
    }

    public function onlinePaths(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('finance');
        $regionId = null;

        if (! $isAdmin) {
            $coordinator = \App\Models\Coordinator::where('user_id', $user->id)->first();
            if ($coordinator) {
                $regionId = $coordinator->region_id;
            }
        }

        $olts = Olt::whereNotNull('latitude')->whereNotNull('longitude')->get();

        $odcQuery = Odc::query()->whereNotNull('latitude')->whereNotNull('longitude');
        if ($regionId) {
            $odcQuery->where('region_id', $regionId);
        }
        $odcs = $odcQuery->get();

        $odpQuery = Odp::query()->whereNotNull('latitude')->whereNotNull('longitude');
        if ($regionId) {
            $odpQuery->where('region_id', $regionId);
        }
        $odps = $odpQuery->get();

        $customerQuery = Customer::whereNotNull('latitude')->whereNotNull('longitude');
        if ($regionId) {
            $customerQuery->whereHas('odp', function ($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }
        $customers = $customerQuery
            ->select(['id', 'name', 'latitude', 'longitude', 'odp_id', 'onu_serial'])
            ->with('odp:id,region_id')
            ->get();

        $customerIds = $customers->pluck('id')->all();
        $serials = $customers->pluck('onu_serial')->filter()->values()->all();
        $statusesByCustomer = GenieDeviceStatus::query()
            ->whereIn('customer_id', $customerIds)
            ->get()
            ->keyBy('customer_id');
        $statusesBySerial = GenieDeviceStatus::query()
            ->whereNull('customer_id')
            ->whereIn('onu_serial', $serials)
            ->get()
            ->keyBy('onu_serial');

        $customers->transform(function ($customer) use ($statusesByCustomer, $statusesBySerial) {
            $status = $statusesByCustomer->get($customer->id);
            if (! $status && $customer->onu_serial) {
                $status = $statusesBySerial->get($customer->onu_serial);
            }
            $customer->is_online = $status ? (bool) $status->is_online : false;

            return $customer;
        });

        $paths = [];
        foreach ($olts as $olt) {
            $odcsByOlt = $odcs->where('olt_id', $olt->id);
            foreach ($odcsByOlt as $odc) {
                $odpsByOdc = $odps->where('odc_id', $odc->id);
                foreach ($odpsByOdc as $odp) {
                    $custs = $customers->where('odp_id', $odp->id)->where('is_online', true);
                    foreach ($custs as $customer) {
                        $pathPoints = [
                            [$olt->latitude, $olt->longitude],
                            [$odc->latitude, $odc->longitude],
                            [$odp->latitude, $odp->longitude],
                            [$customer->latitude, $customer->longitude],
                        ];
                        $paths[] = [
                            'olt' => ['id' => $olt->id, 'name' => $olt->name, 'latitude' => $olt->latitude, 'longitude' => $olt->longitude],
                            'odc' => ['id' => $odc->id, 'name' => $odc->name, 'latitude' => $odc->latitude, 'longitude' => $odc->longitude],
                            'odp' => ['id' => $odp->id, 'name' => $odp->name, 'latitude' => $odp->latitude, 'longitude' => $odp->longitude],
                            'customer' => ['id' => $customer->id, 'name' => $customer->name, 'latitude' => $customer->latitude, 'longitude' => $customer->longitude],
                            'path' => $pathPoints,
                        ];
                    }
                }
            }
        }

        return response()->json(['paths' => array_values($paths)]);
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
            case 'olt': $model = Olt::find($id);
                break;
            case 'odc': $model = Odc::find($id);
                break;
            case 'odp': $model = Odp::find($id);
                break;
            case 'htb': $model = Htb::find($id);
                break;
            case 'closure': $model = Closure::find($id);
                break;
            case 'customer': $model = Customer::find($id);
                break;
            case 'asset': $model = Asset::find($id);
                break;
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
     * Update the path of a resource.
     */
    public function updatePath(Request $request, $type, $id)
    {
        $request->validate([
            'path' => 'nullable|array',
        ]);

        $model = null;
        switch ($type) {
            case 'olt': $model = Olt::find($id);
                break;
            case 'odc': $model = Odc::find($id);
                break;
            case 'odp': $model = Odp::find($id);
                break;
            case 'htb': $model = Htb::find($id);
                break;
            case 'customer': $model = Customer::find($id);
                break;
        }

        if ($model) {
            $model->path = $request->path;
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
