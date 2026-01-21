<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Olt;
use App\Models\Odp;
use App\Models\Coordinator;
use App\Models\Setting;
use App\Services\GenieACSService;
use Illuminate\Http\Request;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Reader\CSV\Reader as CSVReader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            new Middleware('permission:customer.view', only: ['index', 'show', 'import', 'export']),
            new Middleware('permission:customer.create', only: ['create', 'store', 'importFile']),
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

        // Filter for Coordinator (Pengurus)
        if (!Auth::user()->hasRole('admin')) {
            $coordinator = Coordinator::where('user_id', Auth::id())->first();
            if ($coordinator && $coordinator->region_id) {
                $query->whereHas('odp', function($q) use ($coordinator) {
                    $q->where('region_id', $coordinator->region_id);
                });
            }
        }

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
        
        $modemStatuses = [];
        // Optimized: Disable auto-fetch status to improve performance
        // foreach ($customers as $c) {
        //     if (!empty($c->onu_serial)) {
        //         try {
        //             $status = $this->genieService->getDeviceStatus($c->onu_serial);
        //             $modemStatuses[$c->id] = [
        //                 'online' => (bool)($status['online'] ?? false),
        //                 'last_inform' => $status['last_inform'] ?? null,
        //                 'id' => $status['id'] ?? null,
        //             ];
        //         } catch (\Exception $e) {
        //             $modemStatuses[$c->id] = ['online' => false, 'last_inform' => null, 'id' => null];
        //         }
        //     } else {
        //         $modemStatuses[$c->id] = ['online' => false, 'last_inform' => null, 'id' => null];
        //     }
        // }

        return view('customers.index', compact('customers', 'modemStatuses'));
    }

    public function export(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403);
        }

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

        $customers = $query->orderBy('name')->get();

        return response()->streamDownload(function () use ($customers) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([
                'id',
                'name',
                'address',
                'phone',
                'package',
                'ip_address',
                'vlan',
                'odp',
                'status',
                'pppoe_user',
                'pppoe_password',
                'onu_serial',
                'device_model',
                'ssid_name',
                'ssid_password',
                'latitude',
                'longitude',
            ]));

            foreach ($customers as $customer) {
                $writer->addRow(Row::fromValues([
                    $customer->id,
                    $customer->name,
                    $customer->address,
                    $customer->phone,
                    $customer->package,
                    $customer->ip_address,
                    $customer->vlan,
                    $customer->odp,
                    $customer->status,
                    $customer->pppoe_user,
                    $customer->pppoe_password,
                    $customer->onu_serial,
                    $customer->device_model,
                    $customer->ssid_name,
                    $customer->ssid_password,
                    $customer->latitude,
                    $customer->longitude,
                ]));
            }

            $writer->close();
        }, 'customers_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function importFile(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv|max:20480',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            $reader = new CSVReader();
        } else {
            $reader = new XLSXReader();
        }

        $reader->open($file->getRealPath());

        $header = null;
        $created = 0;
        $updated = 0;
        $invalid = 0;
        $missingHeader = false;
        $rowNumber = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                $values = [];
                $rowNumber++;

                foreach ($cells as $cell) {
                    $values[] = $cell->getValue();
                }

                if ($header === null) {
                    $header = array_map(function ($value) {
                        return strtolower(trim((string) $value));
                    }, $values);
                    if (!in_array('name', $header, true)) {
                        $missingHeader = true;
                        break;
                    }
                    continue;
                }

                $isEmpty = true;
                foreach ($values as $value) {
                    if ($value !== null && $value !== '') {
                        $isEmpty = false;
                        break;
                    }
                }
                if ($isEmpty) {
                    continue;
                }

                $data = [];

                $map = [
                    'id' => 'id',
                    'name' => 'name',
                    'address' => 'address',
                    'phone' => 'phone',
                    'package' => 'package',
                    'ip_address' => 'ip_address',
                    'vlan' => 'vlan',
                    'odp' => 'odp',
                    'status' => 'status',
                    'pppoe_user' => 'pppoe_user',
                    'pppoe_password' => 'pppoe_password',
                    'onu_serial' => 'onu_serial',
                    'device_model' => 'device_model',
                    'ssid_name' => 'ssid_name',
                    'ssid_password' => 'ssid_password',
                    'latitude' => 'latitude',
                    'longitude' => 'longitude',
                ];

                $rowId = null;

                foreach ($map as $column => $field) {
                    $index = array_search($column, $header, true);
                    if ($index !== false && array_key_exists($index, $values)) {
                        $value = $values[$index];
                        if ($column === 'id') {
                            $rowId = $value !== null && $value !== '' ? (int) $value : null;
                        } elseif (in_array($column, ['latitude', 'longitude'], true)) {
                            if ($value === null || $value === '' || !is_numeric($value)) {
                                $data[$field] = null;
                            } else {
                                $data[$field] = (float) $value;
                            }
                        } elseif ($column === 'pppoe_user') {
                            if ($value === null || $value === '') {
                                $data[$field] = null;
                            } else {
                                $data[$field] = (string) $value;
                            }
                        } else {
                            $data[$field] = $value !== null ? (string) $value : null;
                        }
                    }
                }

                if (!isset($data['name']) || $data['name'] === null || $data['name'] === '') {
                    $invalid++;
                    continue;
                }

                if (isset($data['status']) && $data['status'] !== null && $data['status'] !== '') {
                    $status = strtolower($data['status']);
                    if (!in_array($status, ['active', 'suspend', 'terminated'], true)) {
                        $data['status'] = 'active';
                    } else {
                        $data['status'] = $status;
                    }
                } else {
                    $data['status'] = 'active';
                }

                if ($rowId) {
                    $customer = Customer::find($rowId);
                    if ($customer) {
                        $customer->update($data);
                        $updated++;
                        continue;
                    }
                }

                Customer::create($data);
                $created++;
            }
        }

        $reader->close();

        if ($missingHeader) {
            return redirect()->route('customers.index')->withErrors([
                'error' => __('Invalid file format. Required column: name.')
            ]);
        }

        return redirect()->route('customers.index')->with('success', __('Imported :created new customers, updated :updated customers.', [
            'created' => $created,
            'updated' => $updated,
        ]))->with('warning', $invalid > 0 ? __('Skipped :count rows due to missing/invalid name.', ['count' => $invalid]) : null);
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
     * Get GenieACS Device Details by Serial
     */
    public function getGenieDevice(Request $request)
    {
        $serial = $request->query('serial');
        if (!$serial) {
            return response()->json(['error' => 'Serial number required'], 400);
        }

        $device = $this->genieService->findDeviceBySerial($serial);
        
        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        $getValue = function($node) {
            if (is_array($node)) {
                return $node['_value'] ?? '';
            }
            return (string) $node;
        };

        // Extract fields
        $ipNode = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['ExternalIPAddress'] 
                 ?? $device['Device']['IP']['Interface'][1]['IPv4Address'][1]['IPAddress'] 
                 ?? '';
        $ip = $getValue($ipNode);

        $vlanNode = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['X_HW_VLAN'] 
                 ?? '';
        $vlan = $getValue($vlanNode);

        $wanMacNode = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['MACAddress'] 
                 ?? '';
        $wanMac = $getValue($wanMacNode);

        $modelNode = $device['_deviceId']['_ProductClass'] 
                 ?? ($device['Device']['DeviceInfo']['ModelName']['_value'] ?? 'Unknown');
        
        $ssidNode = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['SSID']
                 ?? $device['Device']['WiFi']['SSID'][1]['SSID']
                 ?? '';
        $ssid = $getValue($ssidNode);

        $passNode = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['PreSharedKey'][1]['KeyPassphrase']
                 ?? $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['PreSharedKey'][1]['PreSharedKey']
                 ?? $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['KeyPassphrase']
                 ?? $device['Device']['WiFi']['AccessPoint'][1]['Security']['KeyPassphrase']
                 ?? '';
        $wifiPass = $getValue($passNode);

        return response()->json([
            'ip_address' => $ip,
            'vlan' => $vlan,
            'wan_mac' => $wanMac,
            'device_model' => $modelNode,
            'ssid_name' => $ssid,
            'ssid_password' => $wifiPass,
        ]);
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

        $packages = \App\Models\Package::where('is_active', true)->orderBy('name')->get();

        return view('customers.create', compact('prefill', 'odps', 'olts', 'onuDevices', 'packages'));
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
            'package_id' => 'nullable|exists:packages,id',
            'ip_address' => 'nullable|ip',
            'vlan' => 'nullable|string|max:20',
            'odp' => 'nullable|string|max:50',
            'odp_id' => 'nullable|exists:odps,id',
            'olt_id' => 'nullable|exists:olts,id',
            'status' => 'required|in:active,suspend,terminated',
            'pppoe_user' => 'nullable|string|unique:customers,pppoe_user',
            'pppoe_password' => 'nullable|string',
            'onu_serial' => 'nullable|string',
            'wan_mac' => 'nullable|string|max:20',
            'device_model' => 'nullable|string|max:100',
            'ssid_name' => 'nullable|string|max:100',
            'ssid_password' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if (!empty($validated['package_id'])) {
            $pkg = \App\Models\Package::find($validated['package_id']);
            if ($pkg) {
                $validated['package'] = $pkg->name;
            }
        }

        if (!empty($validated['odp_id'])) {
            $odp = Odp::find($validated['odp_id']);
            if ($odp && $odp->isFull()) {
                return back()->withInput()->withErrors(['odp_id' => __('Selected ODP is full.')]);
            }
            if ($odp) {
                $validated['odp'] = $odp->name;
            }
        }

        try {
            DB::transaction(function () use ($validated) {
                $customer = Customer::create($validated);
                if (!empty($validated['odp_id'])) {
                    $odp = Odp::find($validated['odp_id']);
                    if ($odp) {
                        $odp->increment('filled');
                    }
                }
            });
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
        $modemStatus = ['online' => false, 'last_inform' => null];
        if ($customer->onu_serial) {
            $status = $this->genieService->getDeviceStatus($customer->onu_serial);
            if (isset($status['id'])) {
                $genieDeviceId = $status['id'];
            }
            $modemStatus['online'] = (bool)($status['online'] ?? false);
            $modemStatus['last_inform'] = $status['last_inform'] ?? null;
        }

        return view('customers.show', compact('customer', 'genieDeviceId', 'modemStatus'));
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

        $packages = \App\Models\Package::where('is_active', true)->orderBy('name')->get();

        return view('customers.edit', compact('customer', 'odps', 'olts', 'onuDevices', 'packages'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'package_id' => 'nullable|exists:packages,id',
            'package' => 'nullable|string|max:100',
            'ip_address' => 'nullable|ip',
            'vlan' => 'nullable|string|max:20',
            'odp' => 'nullable|string|max:50',
            'odp_id' => 'nullable|exists:odps,id',
            'olt_id' => 'nullable|exists:olts,id',
            'status' => 'sometimes|required|in:active,suspend,terminated',
            'pppoe_user' => 'nullable|string|unique:customers,pppoe_user,' . $customer->id,
            'pppoe_password' => 'nullable|string',
            'onu_serial' => 'nullable|string',
            'wan_mac' => 'nullable|string|max:20',
            'device_model' => 'nullable|string|max:100',
            'ssid_name' => 'nullable|string|max:100',
            'ssid_password' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if (!empty($validated['package_id'])) {
            $pkg = \App\Models\Package::find($validated['package_id']);
            if ($pkg) {
                $validated['package'] = $pkg->name;
            }
        }

        $oldOdpId = $customer->odp_id;
        if (!empty($validated['odp_id'])) {
            $newOdp = Odp::find($validated['odp_id']);
            if ($newOdp && $newOdp->isFull() && $newOdp->id !== $oldOdpId) {
                return back()->withInput()->withErrors(['odp_id' => __('Selected ODP is full.')]);
            }
            if ($newOdp) {
                $validated['odp'] = $newOdp->name;
            }
        }

        DB::transaction(function () use ($customer, $validated, $oldOdpId) {
            $customer->update($validated);
            $newOdpId = $customer->odp_id;
            if ($oldOdpId !== $newOdpId) {
                if ($oldOdpId) {
                    $oldOdp = Odp::find($oldOdpId);
                    if ($oldOdp) {
                        $oldOdp->decrement('filled');
                    }
                }
                if ($newOdpId) {
                    $newOdp = Odp::find($newOdpId);
                    if ($newOdp) {
                        $newOdp->increment('filled');
                    }
                }
            }
        });

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
        DB::transaction(function () use ($customer) {
            $odpId = $customer->odp_id;
            $customer->delete();
            if ($odpId) {
                $odp = Odp::find($odpId);
                if ($odp) {
                    $odp->decrement('filled');
                }
            }
        });
        return redirect()->route('customers.index')->with('success', __('Customer deleted successfully.'));
    }

    public function settings(Request $request, Customer $customer)
    {
        if (!$customer->onu_serial) {
            return redirect()->back()->withErrors(['error' => __('Customer has no ONU Serial assigned.')]);
        }

        // Find device ID
        $status = $this->genieService->getDeviceStatus($customer->onu_serial);
        if (!isset($status['id'])) {
            return redirect()->back()->withErrors(['error' => __('Device not found in GenieACS.')]);
        }
        $deviceId = $status['id'];

        // Optimize: Fetch device details once
        $deviceData = $this->genieService->getDeviceDetails($deviceId);

        $wanConnections = $this->genieService->getWanConnections($deviceId, $deviceData);
        $selectedWanPath = $request->query('wan_path');
        $wanSettings = $this->genieService->getWanSettings($deviceId, $selectedWanPath, $deviceData);
        
        $wlanSettings1 = $this->genieService->getWlanSettings($deviceId, 1, $deviceData);
        $wlanSettings2 = $this->genieService->getWlanSettings($deviceId, 2, $deviceData);
        $wlanSettings3 = $this->genieService->getWlanSettings($deviceId, 3, $deviceData);
        $wlanSettings4 = $this->genieService->getWlanSettings($deviceId, 4, $deviceData);

        return view('customers.settings', compact('customer', 'wanSettings', 'wanConnections', 'selectedWanPath', 'wlanSettings1', 'wlanSettings2', 'wlanSettings3', 'wlanSettings4', 'deviceId'));
    }

    public function updateWan(Request $request, Customer $customer)
    {
        $deviceId = $request->input('device_id');
        if (!$deviceId) return back()->withErrors(['error' => 'Device ID missing']);

        $data = $request->only(['enable', 'conn_name', 'vlan', 'conn_type', 'service', 'username', 'password', 'nat', 'lan_bind']);
        
        $data['enable'] = $request->has('enable');
        $data['nat'] = $request->has('nat');
        
        $path = $request->input('wan_path');

        if ($this->genieService->updateWanAdvanced($deviceId, $data, $path)) {
            return redirect()->back()->with('success', __('WAN Settings updated successfully.'));
        }
        return redirect()->back()->withErrors(['error' => __('Failed to update WAN Settings.')]);
    }

    public function updateWlan(Request $request, Customer $customer)
    {
        $deviceId = $request->input('device_id');
        if (!$deviceId) return back()->withErrors(['error' => 'Device ID missing']);

        $index = $request->input('index', 1);
        $data = $request->only(['enable', 'ssid', 'password', 'security', 'channel', 'auto_channel', 'power']);
        
        $data['enable'] = $request->has('enable');
        $data['auto_channel'] = $request->has('auto_channel');

        if ($this->genieService->updateWlanAdvanced($deviceId, $data, $index)) {
             // Only update local DB if it's the primary SSID (Index 1)
             if ($index == 1) {
                 $customer->update([
                     'ssid_name' => $data['ssid'],
                     'ssid_password' => $data['password']
                 ]);
             }
            return redirect()->back()->with('success', __('WLAN Settings (SSID ' . $index . ') updated successfully.'));
        }
        return redirect()->back()->withErrors(['error' => __('Failed to update WLAN Settings.')]);
    }
}
