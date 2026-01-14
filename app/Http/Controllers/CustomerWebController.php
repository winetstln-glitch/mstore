<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Olt;
use App\Services\GenieACSService;
use Illuminate\Http\Request;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CustomerWebController extends Controller implements HasMiddleware
{
    protected $genieService;

    public function __construct(GenieACSService $genieService)
    {
        $this->genieService = $genieService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:customer.view', only: ['index', 'show', 'import']),
            new Middleware('permission:customer.create', only: ['create', 'store']),
            new Middleware('permission:customer.edit', only: ['edit', 'update']),
            new Middleware('permission:customer.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->has('search') && $request->input('search') != '') {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status') != '') {
            $query->where('status', $request->input('status'));
        }

        $customers = $query->latest()->paginate(10)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    /**
     * Show Import Form
     */
    public function import()
    {
        // 1. Get all devices from GenieACS
        $devices = $this->genieService->getDevices(100); 

        // 2. Get existing ONU Serials
        $existingSerials = Customer::whereNotNull('onu_serial')->pluck('onu_serial')->toArray();

        // 3. Filter and Map devices
        $newDevices = collect($devices)->filter(function($device) use ($existingSerials) {
             $serial = $device['_deviceId']['_SerialNumber'] ?? null;
             return $serial && !in_array($serial, $existingSerials);
        })->map(function($device) {
             $getValue = function($node) {
                 if (is_array($node)) {
                     return $node['_value'] ?? '';
                 }
                 return (string) $node;
             };

             $serial = $device['_deviceId']['_SerialNumber'] ?? 'Unknown';
             
             // Extract IP
             $ipNode = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['ExternalIPAddress'] 
                      ?? $device['Device']['IP']['Interface'][1]['IPv4Address'][1]['IPAddress'] 
                      ?? 'N/A';
             $ip = $getValue($ipNode);

             // Extract Model
             $modelNode = $device['InternetGatewayDevice']['DeviceInfo']['ModelName']
                      ?? $device['Device']['DeviceInfo']['ModelName']
                      ?? 'N/A';
             $model = $getValue($modelNode);

             // Extract SSID (WLAN)
             $ssidNode = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['SSID']
                      ?? $device['Device']['WiFi']['SSID'][1]['SSID']
                      ?? '';
             $ssid = $getValue($ssidNode);

             // Extract Wifi Password
             $passNode = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['PreSharedKey'][1]['KeyPassphrase']
                      ?? $device['Device']['WiFi']['AccessPoint'][1]['Security']['KeyPassphrase']
                      ?? '';
             $wifiPass = $getValue($passNode);

             // Extract Last Inform
             $lastInformRaw = $device['_lastInform'] ?? null;
             $lastInform = $lastInformRaw ? \Carbon\Carbon::parse($lastInformRaw)->format('d M Y H:i') : 'N/A';

             return (object) [
                 'serial' => $serial,
                 'ip' => $ip,
                 'lastInform' => $lastInform,
                 'name' => $ssid ? $ssid : $serial, // Use SSID as name if available
                 'device_model' => $model,
                 'ssid_name' => $ssid,
                 'ssid_password' => $wifiPass,
             ];
        });

        return view('customers.import', compact('newDevices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $prefill = [
            'onu_serial' => $request->query('onu_serial'),
            'ip_address' => $request->query('ip_address'),
            'name' => $request->query('name'),
            'device_model' => $request->query('device_model'),
            'ssid_name' => $request->query('ssid_name'),
            'ssid_password' => $request->query('ssid_password'),
        ];
        $odps = \App\Models\Odp::all();
        $olts = \App\Models\Olt::where('is_active', true)->get();

        // Fetch GenieACS devices
        try {
            $genieDevices = $this->genieService->getDevices(200);
            $onuDevices = collect($genieDevices)->map(function($d) {
                return [
                    'serial' => $d['_deviceId']['_SerialNumber'] ?? '',
                    'model' => $d['_deviceId']['_ProductClass'] ?? ($d['Device']['DeviceInfo']['ModelName']['_value'] ?? 'Unknown'),
                ];
            })->filter(function($d) { return !empty($d['serial']); })->values();
        } catch (\Exception $e) {
            $onuDevices = [];
        }

        return view('customers.create', compact('prefill', 'odps', 'olts', 'onuDevices'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'package' => 'nullable|string|max:100',
            'ip_address' => 'nullable|ip',
            'vlan' => 'nullable|string|max:20',
            'odp' => 'nullable|string|max:50',
            'odp_id' => 'nullable|exists:odps,id',
            'olt_id' => 'nullable|exists:olts,id',
            'status' => 'required|in:active,suspend,terminated',
            'pppoe_user' => 'nullable|string|unique:customers,pppoe_user',
            'pppoe_password' => 'nullable|string',
            'onu_serial' => 'nullable|string',
            'device_model' => 'nullable|string|max:100',
            'ssid_name' => 'nullable|string|max:100',
            'ssid_password' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        // If ODP ID is selected, we can also sync the 'odp' string column for now if needed, or just rely on ID
        if (!empty($validated['odp_id'])) {
            $odp = \App\Models\Odp::find($validated['odp_id']);
            if ($odp) {
                $validated['odp'] = $odp->name;
            }
        }

        try {
            Customer::create($validated);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating customer: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => __('Failed to create customer: :message', ['message' => $e->getMessage()])]);
        }

        return redirect()->route('customers.index')->with('success', __('Customer created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        $customer->load(['tickets', 'installations', 'olt', 'odp']);

        $genieDeviceId = null;
        if ($customer->onu_serial) {
            $status = $this->genieService->getDeviceStatus($customer->onu_serial);
            if (isset($status['id'])) {
                $genieDeviceId = $status['id'];
            }
        }

        return view('customers.show', compact('customer', 'genieDeviceId'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        $odps = \App\Models\Odp::all();
        $olts = \App\Models\Olt::where('is_active', true)->get();
        
        // Fetch GenieACS devices
        try {
            $genieDevices = $this->genieService->getDevices(200);
            $onuDevices = collect($genieDevices)->map(function($d) {
                return [
                    'serial' => $d['_deviceId']['_SerialNumber'] ?? '',
                    'model' => $d['_deviceId']['_ProductClass'] ?? ($d['Device']['DeviceInfo']['ModelName']['_value'] ?? 'Unknown'),
                ];
            })->filter(function($d) { return !empty($d['serial']); })->values();
        } catch (\Exception $e) {
            $onuDevices = [];
        }

        return view('customers.edit', compact('customer', 'odps', 'olts', 'onuDevices'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'package' => 'nullable|string|max:100',
            'ip_address' => 'nullable|ip',
            'vlan' => 'nullable|string|max:20',
            'odp' => 'nullable|string|max:50',
            'odp_id' => 'nullable|exists:odps,id',
            'olt_id' => 'nullable|exists:olts,id',
            'status' => 'required|in:active,suspend,terminated',
            'pppoe_user' => 'nullable|string|unique:customers,pppoe_user,' . $customer->id,
            'pppoe_password' => 'nullable|string',
            'onu_serial' => 'nullable|string',
            'device_model' => 'nullable|string|max:100',
            'ssid_name' => 'nullable|string|max:100',
            'ssid_password' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if (!empty($validated['odp_id'])) {
            $odp = \App\Models\Odp::find($validated['odp_id']);
            if ($odp) {
                $validated['odp'] = $odp->name;
            }
        }

        $customer->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Customer updated successfully.'),
                'data' => $customer
            ]);
        }

        return redirect()->route('customers.index')->with('success', __('Customer updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', __('Customer deleted successfully.'));
    }
}
