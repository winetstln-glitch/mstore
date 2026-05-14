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
use Illuminate\Support\Facades\DB;

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
            new Middleware('permission:map.view', only: ['index', 'onlinePaths', 'show', 'getCustomerWlanStatus', 'ping', 'updateLocation']),
            new Middleware('permission:map.manage', only: ['create', 'store', 'edit', 'update', 'destroy', 'updatePath', 'updateCustomerWlan']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('finance');
        $canManageMap = $user->can('map.manage');
        $canEditCustomer = $user->can('customer.edit');

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

        $customers = $customerQuery->select(['id', 'name', 'address', 'latitude', 'longitude', 'status', 'phone', 'onu_serial', 'odp', 'odp_id', 'package', 'path', 'ssid_name', 'ssid_password', 'genieacs_device_id'])
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
                $customer->last_inform = $status->last_inform?->toIso8601String();
                $customer->last_reason = $status->last_reason;
                $customer->connection_request_url = $status->connection_request_url;
                $customer->has_genie_status = true;
                $customer->rx_power = $status->rx_power;
                $customer->tx_power = $status->tx_power;
                $customer->rdm_power = $status->rdm_power;
                $customer->genie_name = null;
            } else {
                $customer->is_online = false;
                $customer->tr069_ip = null;
                $customer->last_inform = null;
                $customer->last_reason = null;
                $customer->connection_request_url = null;
                $customer->has_genie_status = false;
                $customer->rx_power = null;
                $customer->tx_power = null;
                $customer->rdm_power = null;
                $customer->genie_name = null;
            }

            return $customer;
        });

        // Fetch Assets (Tools) with location
        $assets = Asset::with(['item', 'holder'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $modemDataRecords = DB::table('modem_data_records')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotNull('customer_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('customers')
                    ->whereColumn('customers.id', 'modem_data_records.customer_id');
            })
            ->select(['id', 'customer_id', 'customer_name', 'modem_type', 'mac_address', 'serial_number', 'latitude', 'longitude', 'created_at'])
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('map.index', compact('customers', 'odps', 'htbs', 'odcs', 'olts', 'regions', 'assets', 'modemDataRecords', 'coordinators', 'isAdmin', 'closures', 'canManageMap', 'canEditCustomer'));
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

        $user = auth()->user();
        $canManageMap = $user && $user->can('map.manage');
        $canEditCustomerLocation = $type === 'customer' && $user && $user->can('customer.edit');
        if (! $canManageMap && ! $canEditCustomerLocation) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

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

    /**
     * Get WLAN status for a specific customer from GenieACS.
     */
    public function getCustomerWlanStatus(Customer $customer)
    {
        $deviceId = $customer->genieacs_device_id;

        if (! $deviceId && $customer->onu_serial) {
            $deviceId = $customer->onu_serial;
        }

        if (! $deviceId) {
            return response()->json(['success' => false, 'message' => 'No GenieACS ID available']);
        }

        try {
            // Fetch device with limited fields for performance
            $device = $this->genieService->getDeviceDetails($deviceId);
            
            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Device not found']);
            }

            $wifiClients = $this->genieService->getWifiClients($deviceId, $device);
            $totalClients = 0;
            if (is_array($wifiClients)) {
                foreach ($wifiClients as $ssidIndex => $clients) {
                    if (is_array($clients)) {
                        $totalClients += count($clients);
                    }
                }
            }

            // Extract SSID and Uptime
            $ssid = $this->genieService->getValue(
                data_get($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID') ?? 
                data_get($device, 'Device.WiFi.SSID.1.SSID') ?? 
                $customer->ssid_name
            );

            $wifiPassword = $this->genieService->getValue(
                data_get($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase') ??
                data_get($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase') ??
                data_get($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.PreSharedKey') ??
                data_get($device, 'Device.WiFi.AccessPoint.1.Security.KeyPassphrase') ??
                data_get($device, 'Device.WiFi.AccessPoint.1.Security.PreSharedKey') ??
                $customer->ssid_password
            );

            $uptime = $this->genieService->getValue(
                data_get($device, 'VirtualParameters.getdeviceuptime') ?? 
                data_get($device, 'InternetGatewayDevice.DeviceInfo.UpTime') ?? 
                data_get($device, 'Device.DeviceInfo.UpTime')
            );

            // Determine live online status
            $lastInformRaw = $device['_lastInform'] ?? null;
            $isOnline = false;
            $threshold = (int) \App\Models\Setting::getValue('genieacs_online_threshold_minutes', 15);
            if ($lastInformRaw) {
                $lastInformAt = \Carbon\Carbon::parse($lastInformRaw);
                $isRecent = $lastInformAt->gte(now()->subMinutes($threshold));
                $tooFarInFuture = $lastInformAt->gt(now()->addHours(2));
                $isOnline = $isRecent && ! $tooFarInFuture;
            }

            $tr069Ip = $this->genieService->getTr069Ip($device);

            // Extract Power Levels from GenieACS
            $rxPower = $this->genieService->getValue(data_get($device, 'VirtualParameters.RXPower._value') ?? null);
            $rdmPower = $this->genieService->getValue(data_get($device, 'VirtualParameters.redaman._value') ?? null);

            // Sync with GenieDeviceStatus table
            \App\Models\GenieDeviceStatus::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'onu_serial' => $customer->onu_serial,
                    'is_online' => $isOnline,
                    'last_inform' => $lastInformRaw,
                    'tr069_ip' => $tr069Ip,
                    'connection_request_url' => $this->genieService->getConnectionRequestUrl($device),
                    'rx_power' => $rxPower,
                    'rdm_power' => $rdmPower,
                ]
            );

            return response()->json([
                'success' => true,
                'total_clients' => $totalClients,
                'ssid_name' => $ssid,
                'ssid_password' => $wifiPassword,
                'uptime' => $uptime,
                'is_online' => $isOnline,
                'tr069_ip' => $tr069Ip,
                'last_inform' => $lastInformRaw ? \Carbon\Carbon::parse($lastInformRaw)->toIso8601String() : null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Ping a target host/IP.
     */
    public function ping(Request $request)
    {
        $target = $request->input('target');
        if (! $target) {
            return response()->json(['success' => false, 'message' => 'Target IP/Host is required']);
        }

        // Clean target to avoid command injection
        $target = escapeshellarg($target);

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $count = $isWindows ? '-n 4' : '-c 4';
        $timeout = $isWindows ? '-w 1500' : '-W 2';
        $command = "ping $count $timeout $target";

        exec($command, $output, $result);

        $success = ($result === 0);
        $message = $success ? 'Ping success' : 'Ping failed / Timeout';
        
        if (!$success) {
            $message .= ". Perangkat mungkin online ke ACS tapi tidak bisa di-ping langsung dari server (Firewall/NAT).";
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
            'output' => implode("\n", $output),
        ]);
    }

    /**
     * Update customer WLAN settings via GenieACS.
     */
    public function updateCustomerWlan(Request $request, Customer $customer)
    {
        $request->validate([
            'ssid' => 'required|string|max:32',
            'password' => 'required|string|min:8',
        ]);

        $deviceId = $customer->genieacs_device_id ?: $customer->onu_serial;

        if (! $deviceId) {
            return response()->json(['success' => false, 'message' => 'No GenieACS ID available']);
        }

        try {
            $data = [
                'ssid_2g' => $request->ssid,
                'password_2g' => $request->password,
                'ssid_5g' => $request->ssid, // Usually same for simplicity in this context
                'password_5g' => $request->password,
            ];

            $result = $this->genieService->updateWlanSettings($deviceId, $data);

            if ($result['success']) {
                // Also update local database
                $customer->update([
                    'ssid_name' => $request->ssid,
                    'ssid_password' => $request->password,
                ]);

                $message = $result['message'] ?? 'WiFi settings updated successfully';
                if (($result['status'] ?? '') === 'queued') {
                    $message = 'WiFi sedang diproses (Antrian GenieACS). Perubahan akan diterapkan saat perangkat aktif.';
                }

                return response()->json([
                    'success' => true, 
                    'message' => $message,
                    'status' => $result['status'] ?? 'immediate'
                ]);
            }

            return response()->json([
                'success' => false, 
                'message' => $result['message'] ?? 'Failed to update WiFi settings via GenieACS'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
