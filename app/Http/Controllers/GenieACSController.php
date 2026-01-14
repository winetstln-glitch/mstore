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
        $perPage = 50;
        $page = $request->input('page', 1);
        $skip = ($page - 1) * $perPage;

        // Fetch data with pagination
        $devicesArray = $this->genieService->getDevices($perPage, $skip);
        $totalDevices = $this->genieService->getTotalDevices();
        
        // Create manual paginator
        $devices = new LengthAwarePaginator(
            $devicesArray,
            $totalDevices,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $activeServer = GenieAcsServer::where('is_active', true)->first();
        
        $aliases = GenieAcsDeviceSetting::pluck('alias', 'device_id');
        
        return view('genieacs.index', compact('devices', 'totalDevices', 'activeServer', 'aliases'));
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
    public function show($id)
    {
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
        
        return view('genieacs.show', compact('device', 'id', 'config', 'parameters', 'deviceIp', 'customer', 'odps', 'regions'));
    }

    /**
     * Refresh Device (Summon)
     */
    public function refresh($id)
    {
        $success = $this->genieService->refreshObject($id);
        if ($success) {
            return back()->with('success', 'Summon (Connection Request) sent to device.');
        }
        return back()->with('error', 'Failed to summon device.');
    }

    /**
     * Reboot Device
     */
    public function reboot($id)
    {
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
        $request->validate([
            'pppoe_user' => 'required|string',
            'pppoe_password' => 'required|string',
            'vlan_id' => 'nullable|integer',
        ]);

        $success = $this->genieService->updateWanSettings(
            $id, 
            $request->pppoe_user, 
            $request->pppoe_password, 
            $request->vlan_id
        );

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
        $request->validate([
            'ssid_2g' => 'nullable|string',
            'password_2g' => 'nullable|string|min:8',
            'ssid_5g' => 'nullable|string',
            'password_5g' => 'nullable|string|min:8',
        ]);

        $data = $request->only(['ssid_2g', 'password_2g', 'ssid_5g', 'password_5g']);

        $success = $this->genieService->updateWlanSettings($id, $data);

        if ($success) {
            return back()->with('success', 'WiFi settings update queued.');
        }
        return back()->with('error', 'Failed to update WiFi settings. Device might be offline or model unsupported.');
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
