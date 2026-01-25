<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\GenieAcsServer;
use App\Models\GenieAcsDeviceSetting;
use App\Models\Odp;
use App\Models\Region;
use App\Services\GenieACSService;
use Illuminate\Http\Request;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

use Illuminate\Pagination\LengthAwarePaginator;

class GenieACSController extends Controller implements HasMiddleware
{
    protected $genieService;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:genieacs.view', only: ['index', 'show']),
            new Middleware('permission:genieacs.manage', only: ['refresh', 'updateWan', 'updateWlan', 'updateParam', 'updateAlias', 'reboot', 'ping']),
        ];
    }

    public function __construct(GenieACSService $genieService)
    {
        $this->genieService = $genieService;
    }

    /**
     * Dashboard Monitor: List all devices
     */
    public function index(Request $request)
    {
        $perPageInput = $request->input('per_page', 50);

        if ($perPageInput === 'all') {
            $perPage = null;
        } else {
            $perPage = in_array((int) $perPageInput, [20, 50, 100]) ? (int) $perPageInput : 50;
        }

        $page = $request->input('page', 1);

        $servers = GenieAcsServer::orderBy('name')->get();
        $serverId = $request->input('server_id');
        $modeAll = $serverId === 'all';

        $activeServer = null;
        $devices = null; // Initialize variable

        if ($modeAll) {
            $devicesArray = [];

            foreach ($servers as $server) {
                $this->genieService->useServer($server);
                $serverDevices = $this->genieService->getDevices(500, 0);

                foreach ($serverDevices as &$device) {
                    $device['_mstore_server_id'] = $server->id;
                    $device['_mstore_server_name'] = $server->name;
                }

                $devicesArray = array_merge($devicesArray, $serverDevices);
            }

            $totalDevices = count($devicesArray);

            if ($perPage === null) {
                $perPageEffective = max(1, $totalDevices);
            } else {
                $perPageEffective = $perPage;
            }

            $offset = ($page - 1) * $perPageEffective;
            $itemsForPage = array_slice($devicesArray, $offset, $perPageEffective);

            $devices = new LengthAwarePaginator(
                $itemsForPage,
                $totalDevices,
                $perPageEffective,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            if ($serverId) {
                $activeServer = $servers->firstWhere('id', (int) $serverId);
            }

            if (!$activeServer) {
                $activeServer = $servers->firstWhere('is_active', true) ?? $servers->first();
            }

            if ($activeServer) {
                $this->genieService->useServer($activeServer);
            }

            $query = $request->input('q');

            // Handle case where no servers exist
            if (!$activeServer && $servers->isEmpty()) {
                 $devices = new LengthAwarePaginator([], 0, $perPage ?: 20, 1);
            } else {
                $devicesList = $this->genieService->getDevices($perPage, ($page - 1) * $perPage, $query);
                $total = $this->genieService->getTotalDevices($query);
    
                $devices = new LengthAwarePaginator(
                    $devicesList,
                    $total,
                    $perPage,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            }
        }

        // --- ODP Matching Logic ---
        $customerMap = ['pppoe' => [], 'sn' => []];
        try {
            // Ensure columns exist to prevent 500 error if migration missing
            if (\Illuminate\Support\Facades\Schema::hasColumn('customers', 'odp_id') && 
                \Illuminate\Support\Facades\Schema::hasColumn('customers', 'username_pppoe')) {
                
                $customers = Customer::with('odp:id,name')->get(['id', 'odp_id', 'username_pppoe', 'ont_sn']);
                foreach ($customers as $customer) {
                    $odpName = $customer->odp ? $customer->odp->name : '-';
                    if ($customer->username_pppoe) {
                        $customerMap['pppoe'][strtolower($customer->username_pppoe)] = $odpName;
                    }
                    if ($customer->ont_sn) {
                         $customerMap['sn'][$customer->ont_sn] = $odpName;
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('GenieACS ODP Logic Error: ' . $e->getMessage());
        }

        if ($devices) {
            $devices->getCollection()->transform(function ($device) use ($customerMap) {
                $odpName = '-';
                
                // Try matching by PPPoE Username
                $pppoe = data_get($device, 'VirtualParameters.pppoeUsername._value');
                if ($pppoe && is_string($pppoe) && isset($customerMap['pppoe'][strtolower($pppoe)])) {
                    $odpName = $customerMap['pppoe'][strtolower($pppoe)];
                } 
                // Try matching by Serial Number
                else {
                    $sn = data_get($device, '_deviceId._SerialNumber');
                    if ($sn && is_string($sn) && isset($customerMap['sn'][$sn])) {
                         $odpName = $customerMap['sn'][$sn];
                    }
                }
    
                $device['odp_name'] = $odpName;
                return $device;
            });
        }

        return view('genieacs.index', compact('devices', 'servers', 'activeServer', 'modeAll'));
    }

    /**
     * Update Device Alias
     */
    public function updateAlias(Request $request, $id)
    {
        $request->validate([
            'alias' => 'nullable|string|max:255',
        ]);

        GenieAcsDeviceSetting::updateOrCreate(
            ['device_id' => $id],
            ['alias' => $request->alias]
        );

        return back()->with('success', __('Device alias updated.'));
    }

    /**
     * Device Details: Show tabs for WAN, WLAN, etc.
     */
    public function show(Request $request, $id)
    {
        $serverId = $request->query('server_id');
        if ($serverId && $serverId !== 'all') {
            $server = GenieAcsServer::find((int) $serverId);
            if ($server) {
                $this->genieService->useServer($server);
            }
        } else {
            $serverId = null;
        }

        $device = $this->genieService->getDeviceDetails($id);
        
        if (!$device) {
            return redirect()->route('genieacs.index')->with('error', __('Device not found or offline.'));
        }

        $config = $this->genieService->extractConfiguration($device);
        $parameters = $this->genieService->flattenParameters($device);
        $deviceIp = $this->genieService->getIpAddress($device);

        // Find associated customer
        $serialNumber = data_get($device, '_deviceId._SerialNumber');
        $customer = null;
        if ($serialNumber) {
            $customer = Customer::where('onu_serial', $serialNumber)->first();
        }

        // Get all ODPs and Regions for the dropdowns
        $odps = Odp::with('region')->orderBy('name')->get();
        $regions = Region::orderBy('name')->get();

        // Advanced Settings
        $wanConnections = $this->genieService->getWanConnections($id, $device);
        $selectedWanPath = $request->query('wan_path');
        $wanSettings = $this->genieService->getWanSettings($id, $selectedWanPath, $device);
        
        $wlanSettings1 = $this->genieService->getWlanSettings($id, 1, $device);
        $wlanSettings2 = $this->genieService->getWlanSettings($id, 2, $device);
        $wlanSettings3 = $this->genieService->getWlanSettings($id, 3, $device);
        $wlanSettings4 = $this->genieService->getWlanSettings($id, 4, $device);

        $wifiClients = $this->genieService->getWifiClients($id, $device);
        
        return view('genieacs.show', compact(
            'device', 'id', 'config', 'parameters', 'deviceIp', 'customer', 'odps', 'regions', 'serverId',
            'wanSettings', 'wanConnections', 'selectedWanPath', 'wlanSettings1', 'wlanSettings2', 'wlanSettings3', 'wlanSettings4',
            'wifiClients'
        ));
    }

    /**
     * Refresh Device (Summon)
     */
    public function refresh(Request $request, $id)
    {
        $serverId = $request->query('server_id');
        if ($serverId && $serverId !== 'all') {
            $server = GenieAcsServer::find((int) $serverId);
            if ($server) {
                $this->genieService->useServer($server);
            }
        }

        $status = $this->genieService->refreshObject($id);

        if ($status === 2) {
            // Wait a few seconds to allow GenieACS to update the LastInform timestamp
            sleep(3);
            return back()->with('success', __('Device Connected & Refreshed Successfully.'));
        } elseif ($status === 1) {
            return back()->with('warning', __('Command Queued. Device is online but busy. Task will run shortly.'));
        }

        return back()->with('error', __('Failed to summon device.'));
    }

    /**
     * Reboot Device
     */
    public function reboot(Request $request, $id)
    {
        $serverId = $request->query('server_id');
        if ($serverId && $serverId !== 'all') {
            $server = GenieAcsServer::find((int) $serverId);
            if ($server) {
                $this->genieService->useServer($server);
            }
        }

        $success = $this->genieService->rebootDevice($id);
        if ($success) {
            return back()->with('success', 'Reboot command sent to device.');
        }
        return back()->with('error', 'Failed to reboot device.');
    }

    /**
     * Ping Device
     */
    public function ping(Request $request, $id)
    {
        $serverId = $request->query('server_id');
        if ($serverId && $serverId !== 'all') {
            $server = GenieAcsServer::find((int) $serverId);
            if ($server) {
                $this->genieService->useServer($server);
            }
        }

        $request->validate([
            'host' => 'required|string|ipv4'
        ]);

        $result = $this->genieService->ping($id, $request->host);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }
        return back()->with('error', $result['message']);
    }

    /**
     * Update WAN Settings
     */
    public function updateWan(Request $request, $id)
    {
        $serverId = $request->query('server_id');
        if ($serverId && $serverId !== 'all') {
            $server = GenieAcsServer::find((int) $serverId);
            if ($server) {
                $this->genieService->useServer($server);
            }
        }

        // Validate Advanced Fields
        $request->validate([
            'conn_name' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'vlan' => 'nullable|string',
            'service' => 'nullable|string',
            'conn_type' => 'nullable|string',
        ]);

        $data = $request->only([
            'enable', 'conn_name', 'vlan', 'conn_type', 'service', 
            'username', 'password', 'nat', 'lan_bind'
        ]);

        // Convert checkbox to boolean
        $data['enable'] = $request->has('enable');
        $data['nat'] = $request->has('nat');

        $path = $request->input('wan_path');

        $success = $this->genieService->updateWanAdvanced($id, $data, $path);

        if ($success) {
            return back()->with('success', __('WAN settings update queued.'));
        }
        return back()->with('error', __('Failed to update WAN settings. Device might be offline or model unsupported.'));
    }

    /**
     * Update WLAN (SSID) Settings
     */
    public function updateWlan(Request $request, $id)
    {
        $serverId = $request->query('server_id');
        if ($serverId && $serverId !== 'all') {
            $server = GenieAcsServer::find((int) $serverId);
            if ($server) {
                $this->genieService->useServer($server);
            }
        }

        $request->validate([
            'ssid' => 'nullable|string|max:32',
            'password' => 'nullable|string|max:63',
            'index' => 'required|integer|min:1|max:4',
        ]);

        $index = $request->input('index', 1);
        $data = $request->only(['ssid', 'password', 'security', 'channel', 'power']);

        // Checkbox
        $data['enable'] = $request->has('enable');
        $data['auto_channel'] = $request->has('auto_channel');

        $success = $this->genieService->updateWlanAdvanced($id, $data, $index);

        if ($success) {
            return back()->with('success', __('WiFi settings update queued for SSID ' . $index));
        }
        return back()->with('error', __('Failed to update WiFi settings. Device might be offline or model unsupported.'));
    }

    /**
     * Custom Parameter Update
     */
    public function updateParam(Request $request, $id)
    {
        $request->validate([
            'parameter_name' => 'required|string',
            'parameter_value' => 'required|string',
        ]);

        $params = [
            $request->parameter_name => $request->parameter_value
        ];

        $success = $this->genieService->setParameterValues($id, $params);

        if ($success) {
            return back()->with('success', __('Parameter update queued.'));
        }
        return back()->with('error', __('Failed to update parameter.'));
    }
}
