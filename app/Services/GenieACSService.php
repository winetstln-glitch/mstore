<?php

namespace App\Services;

use App\Models\GenieAcsServer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenieACSService
{
    protected $baseUrl;
    protected $timeout = 30;

    public function __construct()
    {
        $server = GenieAcsServer::where('is_active', true)->first();
        
        if ($server) {
            $this->baseUrl = rtrim($server->url, '/');
        } else {
            $this->baseUrl = config('services.genieacs.url', 'http://genieacs:7557');
        }
    }

    public function useServer(?GenieAcsServer $server): self
    {
        if ($server) {
            $this->baseUrl = rtrim($server->url, '/');
        } else {
            $this->baseUrl = config('services.genieacs.url', 'http://genieacs:7557');
        }

        return $this;
    }

    /**
     * Get list of devices (simplified projection)
     */
    public function getDevices($limit = 50, $skip = 0)
    {
        try {
            // Updated projection based on user request including VirtualParameters
            $projection = implode(',', [
                '_id',
                '_lastInform',
                '_deviceId._SerialNumber',
                '_deviceId._ProductClass',
                '_deviceId._OUI',
                // User requested parameters
                'VirtualParameters.pppoeUsername',
                'VirtualParameters.pppoeUsername2',
                'VirtualParameters.gettemp',
                'VirtualParameters.RXPower',
                'VirtualParameters.pppoeIP',
                'VirtualParameters.IPTR069',
                'VirtualParameters.pppoeMac',
                'VirtualParameters.getponmode',
                'VirtualParameters.PonMac',
                'VirtualParameters.getSerialNumber',
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations',
                'VirtualParameters.activedevices',
                'VirtualParameters.getdeviceuptime',
                'DeviceID.ProductClass',
                'Events.Registered',
                'Events.Inform',
                // Standard fallbacks
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress',
                'Device.IP.Interface.1.IPv4Address.1.IPAddress',
            ]);

            $queryParams = [
                'projection' => $projection,
                'sort' => '{"_lastInform":-1}',
            ];

            if ($limit !== null) {
                $queryParams['limit'] = $limit;
            }
            
            if ($skip > 0) {
                $queryParams['skip'] = $skip;
            }

            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/devices", $queryParams);

            if ($response->successful()) {
                return $response->json();
            }
            Log::error("GenieACS API Error: " . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error("GenieACS Connection Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total device count
     */
    public function getTotalDevices()
    {
        try {
            // Try to get count from headers first with minimal data
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/devices", [
                    'projection' => '_id',
                    'limit' => 1
                ]);

            if ($response->successful()) {
                if ($response->header('Total')) {
                    return (int)$response->header('Total');
                }
                
                // Fallback: Fetch all IDs (up to 2000) to count manually
                $response = Http::timeout($this->timeout)
                    ->get("{$this->baseUrl}/devices", [
                        'projection' => '_id',
                        'limit' => 2000
                    ]);
                    
                if ($response->successful()) {
                    return count($response->json());
                }
            }
            return 0;
        } catch (\Exception $e) {
            Log::error("GenieACS Count Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get full device details
     */
    public function getDeviceDetails($deviceId)
    {
        try {
            // Method 1: Direct ID lookup (try first)
            $encodedId = urlencode($deviceId);
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/devices/{$encodedId}");

            if ($response->successful()) {
                return $response->json();
            }

            // Method 2: Query by _id (Fallback if direct lookup fails due to encoding)
            // This handles cases where ID might have special chars that URL path doesn't like
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/devices", [
                    'query' => json_encode(['_id' => $deviceId])
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data) && is_array($data)) {
                    return $data[0];
                }
            }

            Log::error("GenieACS Details Failed: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("GenieACS Details Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Device IP Address
     */
    public function getIpAddress($device)
    {
        if (!$device) return null;

        // 1. Try VirtualParameters (Custom scripts often put real IP here)
        if (isset($device['VirtualParameters']['pppoeIP']['_value']) && filter_var($device['VirtualParameters']['pppoeIP']['_value'], FILTER_VALIDATE_IP)) {
            return $device['VirtualParameters']['pppoeIP']['_value'];
        }
        
        if (isset($device['VirtualParameters']['IPTR069']['_value']) && filter_var($device['VirtualParameters']['IPTR069']['_value'], FILTER_VALIDATE_IP)) {
            return $device['VirtualParameters']['IPTR069']['_value'];
        }

        // 2. Try Standard TR-098
        if (isset($device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalIPAddress']['_value'])) {
             return $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1]['ExternalIPAddress']['_value'];
        }
        
        if (isset($device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['ExternalIPAddress']['_value'])) {
             return $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANIPConnection'][1]['ExternalIPAddress']['_value'];
        }

        // 3. Try TR-181
        if (isset($device['Device']['IP']['Interface'][1]['IPv4Address'][1]['IPAddress']['_value'])) {
            return $device['Device']['IP']['Interface'][1]['IPv4Address'][1]['IPAddress']['_value'];
        }

        return null;
    }

    /**
     * Refresh Object (Connection Request)
     */
    public function refreshObject($deviceId, $objectName = '')
    {
        try {
            $encodedId = urlencode($deviceId);
            // POST /devices/{id}/tasks?timeout=3000&connection_request
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$encodedId}/tasks?timeout=3000&connection_request", [
                    'name' => 'refreshObject',
                    'objectName' => $objectName
                ]);

            if ($response->successful()) {
                return true;
            }
            Log::error("GenieACS Refresh Failed: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("GenieACS Refresh Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reboot Device
     */
    public function rebootDevice($deviceId)
    {
        try {
            $encodedId = urlencode($deviceId);
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$encodedId}/tasks?timeout=3000&connection_request", [
                    'name' => 'reboot'
                ]);

            if ($response->successful()) {
                return true;
            }
            Log::error("GenieACS Reboot Failed: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("GenieACS Reboot Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Factory Reset Device
     */
    public function factoryReset($deviceId)
    {
        try {
            $encodedId = urlencode($deviceId);
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$encodedId}/tasks?timeout=3000&connection_request", [
                    'name' => 'factoryReset'
                ]);

            if ($response->successful()) {
                return true;
            }
            Log::error("GenieACS Reset Failed: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("GenieACS Reset Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set Parameter Values
     */
    public function setParameterValues($deviceId, array $params)
    {
        $encodedId = urlencode($deviceId);
        $parameterValues = [];
        foreach ($params as $key => $value) {
            // Determine type based on value or force string? 
            // GenieACS usually handles type inference or requires explicit type.
            // For simplicity, sending as string/boolean based on PHP type.
            $type = 'xsd:string';
            if (is_bool($value)) {
                $type = 'xsd:boolean';
                $value = $value ? 'true' : 'false'; // GenieACS often expects string representation
            } elseif (is_int($value)) {
                $type = 'xsd:unsignedInt';
            }

            $parameterValues[] = [
                'name' => $key,
                'value' => (string)$value,
                // 'type' => $type // Optional, GenieACS tries to guess
            ];
        }

        // Attempt 1: Immediate execution (connection_request)
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$encodedId}/tasks?timeout=3000&connection_request", [
                    'name' => 'setParameterValues',
                    'parameterValues' => $parameterValues
                ]);

            if ($response->successful()) {
                return true;
            }
            Log::warning("GenieACS SetParam Immediate Failed: " . $response->body());
        } catch (\Exception $e) {
            Log::error("GenieACS SetParam Immediate Error: " . $e->getMessage());
        }

        // Attempt 2: Fallback to Queue only (no connection_request)
        // This avoids timeouts or empty replies if the device is unreachable immediately
        try {
            Log::info("GenieACS: Retrying SetParam as Queued Task for $deviceId");
            // Remove connection_request param and timeout to just queue it
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$encodedId}/tasks", [
                    'name' => 'setParameterValues',
                    'parameterValues' => $parameterValues
                ]);

            if ($response->successful()) {
                return true;
            }
            
            // If even queuing fails, it might be a server issue, but let's try one more thing:
            // Sometimes empty reply means it worked but connection dropped.
            // Check if we got an empty body but 200 OK? No, successful() checks status.
            // cURL 52 usually means the server dropped the connection.
            
            Log::error("GenieACS SetParam Queue Failed: " . $response->body());
            
            // If the error is cURL 52 (Empty Reply), it's possible the server is just overloaded/badly configured
            // but the request MIGHT have been processed.
            // However, we can't be sure.
            
            return false;
        } catch (\Exception $e) {
            Log::error("GenieACS SetParam Queue Error: " . $e->getMessage());
            
            // HOTFIX: If it's cURL error 52, it often means the GenieACS NBI server crashed/restarted 
            // OR the network is flaky, BUT sometimes the task is actually created.
            // Given the user's persistence, let's assume if it's a network error on the queue attempt,
            // we might want to tell the user to check later or assume it's queued if the server is just wonky.
            // BUT returning true here is dangerous.
            
            return false;
        }
    }

    /**
     * Update WAN Settings (Smart Path Detection)
     */
    public function updateWanSettings($deviceId, $username, $password, $vlanId = null)
    {
        // 1. Get device to check model type
        $device = $this->getDeviceDetails($deviceId);
        if (!$device) return false;

        $params = [];
        
        // 2. Detect Root (IGD vs Device)
        if (isset($device['InternetGatewayDevice'])) {
            // TR-098
            // Find the correct WAN Connection instance
            // Simplified: Default to .1.1.1, but in reality should iterate
            $base = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1';
            
            // Check if PPP exists instead of IP
            if (isset($device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1]['WANPPPConnection'][1])) {
                $base = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1';
            }

            $params["$base.Username"] = $username;
            $params["$base.Password"] = $password;
            if ($vlanId) {
                // VLAN param varies widely by vendor. Common ones:
                // X_BROADCOM_COM_VlanMuxID, X_HW_VLAN, X_CT_COM_VlanID
                // For now, try a few common ones or just the Broadcom one as requested
                $params["$base.X_BROADCOM_COM_VlanMuxID"] = $vlanId;
            }

        } elseif (isset($device['Device'])) {
            // TR-181
            // Usually Device.PPP.Interface.1.Username
            $base = 'Device.PPP.Interface.1';
            $params["$base.Username"] = $username;
            $params["$base.Password"] = $password;
            if ($vlanId) {
                // Device.Ethernet.Link.1.X_...
            }
        } else {
            return false; // Unknown model
        }

        return $this->setParameterValues($deviceId, $params);
    }

    /**
     * Update WLAN Settings (Supports Dual Band)
     */
    public function updateWlanSettings($deviceId, $data)
    {
        $device = $this->getDeviceDetails($deviceId);
        if (!$device) {
            Log::error("GenieACS UpdateWlan: Device not found $deviceId");
            return false;
        }

        $params = [];
        Log::info("GenieACS UpdateWlan Request for $deviceId", $data);

        if (isset($device['InternetGatewayDevice'])) {
            // TR-098
            // 2.4GHz (WLAN 1)
            if (isset($data['ssid_2g'])) {
                $base = 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1';
                $params["$base.SSID"] = $data['ssid_2g'];
                
                if (isset($data['password_2g'])) {
                    // Check which password param is used
                    if (isset($device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['KeyPassphrase'])) {
                         $params["$base.KeyPassphrase"] = $data['password_2g'];
                    } else {
                         // Default to PreSharedKey
                         $params["$base.PreSharedKey.1.PreSharedKey"] = $data['password_2g'];
                    }
                }
            }

            // 5GHz (WLAN 2)
            if (isset($data['ssid_5g'])) {
                // Check if index 2 exists
                if (isset($device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][2])) {
                    $base = 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2';
                    $params["$base.SSID"] = $data['ssid_5g'];
                    
                    if (isset($data['password_5g'])) {
                        if (isset($device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][2]['KeyPassphrase'])) {
                             $params["$base.KeyPassphrase"] = $data['password_5g'];
                        } else {
                             $params["$base.PreSharedKey.1.PreSharedKey"] = $data['password_5g'];
                        }
                    }
                } else {
                    Log::warning("GenieACS: 5GHz WLAN requested but not found at index 2 for $deviceId");
                }
            }
            
        } elseif (isset($device['Device'])) {
            // TR-181
            // 2.4GHz (Index 1)
            if (isset($data['ssid_2g'])) {
                $base = 'Device.WiFi.SSID.1';
                $params["$base.SSID"] = $data['ssid_2g'];
                
                if (isset($data['password_2g'])) {
                    $apBase = 'Device.WiFi.AccessPoint.1.Security';
                    // Check if KeyPassphrase exists (TR-181 usually uses KeyPassphrase)
                    $params["$apBase.KeyPassphrase"] = $data['password_2g'];
                }
            }

            // 5GHz (Index 2)
            if (isset($data['ssid_5g'])) {
                // Check if index 2 exists
                if (isset($device['Device']['WiFi']['SSID'][2])) {
                    $base = 'Device.WiFi.SSID.2';
                    $params["$base.SSID"] = $data['ssid_5g'];
                    
                    if (isset($data['password_5g'])) {
                        $apBase = 'Device.WiFi.AccessPoint.2.Security';
                        $params["$apBase.KeyPassphrase"] = $data['password_5g'];
                    }
                } else {
                    Log::warning("GenieACS: 5GHz WLAN (TR-181) requested but not found at index 2 for $deviceId");
                }
            }
        } else {
            Log::error("GenieACS UpdateWlan: Unknown device model structure");
            return false;
        }

        if (empty($params)) {
            Log::warning("GenieACS UpdateWlan: No parameters to update for $deviceId");
            return false;
        }

        return $this->setParameterValues($deviceId, $params);
    }

    /**
     * Helper to safely extract value from GenieACS node
     */
    private function getValue($node)
    {
        if (is_array($node)) {
            return $node['_value'] ?? '';
        }
        return (string) $node;
    }

    /**
     * Extract normalized configuration from device data
     */
    public function extractConfiguration($device)
    {
        $config = [
            'wan_user' => '',
            'wan_pass' => '',
            'wan_vlan' => '',
            'ssid' => '',
            'wifi_pass' => '',
            'wifi_enabled' => false,
            'wlan_ssid_2g' => '',
            'wlan_pass_2g' => '',
            'wlan_ssid_5g' => '',
            'wlan_pass_5g' => '',
        ];

        if (!$device) return $config;

        // TR-098 (IGD)
        if (isset($device['InternetGatewayDevice'])) {
            // WAN
            $wanBase = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1] ?? [];
            
            if (isset($wanBase['WANPPPConnection'][1])) {
                $conn = $wanBase['WANPPPConnection'][1];
                $config['wan_user'] = $this->getValue($conn['Username'] ?? '');
                $config['wan_pass'] = $this->getValue($conn['Password'] ?? '');
                $config['wan_vlan'] = $this->getValue($conn['X_BROADCOM_COM_VlanMuxID'] ?? '');
            } elseif (isset($wanBase['WANIPConnection'][1])) {
                $conn = $wanBase['WANIPConnection'][1];
                $config['wan_user'] = $this->getValue($conn['Username'] ?? '');
                $config['wan_pass'] = $this->getValue($conn['Password'] ?? '');
                $config['wan_vlan'] = $this->getValue($conn['X_BROADCOM_COM_VlanMuxID'] ?? '');
            }

            // WLAN 1 (2.4GHz)
            $wlan1 = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1] ?? [];
            $config['wlan_ssid_2g'] = $this->getValue($wlan1['SSID'] ?? '');
            $config['wifi_enabled'] = filter_var($this->getValue($wlan1['Enable'] ?? false), FILTER_VALIDATE_BOOLEAN);
            
            // Wifi Password 1
            if (isset($wlan1['PreSharedKey'][1])) {
                $config['wlan_pass_2g'] = $this->getValue($wlan1['PreSharedKey'][1]['PreSharedKey'] ?? '');
            } elseif (isset($wlan1['KeyPassphrase'])) {
                $config['wlan_pass_2g'] = $this->getValue($wlan1['KeyPassphrase'] ?? '');
            }

            // WLAN 2 (5GHz)
            if (isset($device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][2])) {
                $wlan2 = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][2];
                $config['wlan_ssid_5g'] = $this->getValue($wlan2['SSID'] ?? '');
                
                // Wifi Password 2
                if (isset($wlan2['PreSharedKey'][1])) {
                    $config['wlan_pass_5g'] = $this->getValue($wlan2['PreSharedKey'][1]['PreSharedKey'] ?? '');
                } elseif (isset($wlan2['KeyPassphrase'])) {
                    $config['wlan_pass_5g'] = $this->getValue($wlan2['KeyPassphrase'] ?? '');
                }
            }

            // Backward compatibility
            $config['ssid'] = $config['wlan_ssid_2g'];
            $config['wifi_pass'] = $config['wlan_pass_2g'];

        } 
        // TR-181 (Device)
        elseif (isset($device['Device'])) {
            // WAN (PPP)
            if (isset($device['Device']['PPP']['Interface'][1])) {
                $conn = $device['Device']['PPP']['Interface'][1];
                $config['wan_user'] = $this->getValue($conn['Username'] ?? '');
                $config['wan_pass'] = $this->getValue($conn['Password'] ?? '');
            }

            // WLAN 1 (2.4GHz)
            if (isset($device['Device']['WiFi']['SSID'][1])) {
                $ssidObj = $device['Device']['WiFi']['SSID'][1];
                $config['wlan_ssid_2g'] = $this->getValue($ssidObj['SSID'] ?? '');
                $config['wifi_enabled'] = filter_var($this->getValue($ssidObj['Enable'] ?? false), FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($device['Device']['WiFi']['AccessPoint'][1]['Security'])) {
                $sec = $device['Device']['WiFi']['AccessPoint'][1]['Security'];
                $config['wlan_pass_2g'] = $this->getValue($sec['KeyPassphrase'] ?? '');
            }

            // WLAN 2 (5GHz)
            if (isset($device['Device']['WiFi']['SSID'][2])) {
                $ssidObj2 = $device['Device']['WiFi']['SSID'][2];
                $config['wlan_ssid_5g'] = $this->getValue($ssidObj2['SSID'] ?? '');
            }
            if (isset($device['Device']['WiFi']['AccessPoint'][2]['Security'])) {
                $sec2 = $device['Device']['WiFi']['AccessPoint'][2]['Security'];
                $config['wlan_pass_5g'] = $this->getValue($sec2['KeyPassphrase'] ?? '');
            }

            // Backward compatibility
            $config['ssid'] = $config['wlan_ssid_2g'];
            $config['wifi_pass'] = $config['wlan_pass_2g'];
        }

        return $config;
    }

    /**
     * Get device status by Serial Number (or OUI+ProductClass+Serial)
     */
    public function getDeviceStatus($serialNumber)
    {
        try {
            // Mocking the query structure for GenieACS NBI
            // Usually: /devices/?query={"_id":"<device_id>"} or by serial
            // GenieACS ID is often OUI-ProductClass-SerialNumber
            
            // For implementation simplicity, assuming we search by Serial
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/devices", [
                    'query' => json_encode(['_deviceId._SerialNumber' => $serialNumber]),
                    'projection' => '_lastInform'
                ]);

            if ($response->successful()) {
                $devices = $response->json();
                if (!empty($devices)) {
                    $device = $devices[0];
                    // Check if last inform was recent (e.g., within 5 minutes)
                    $lastInform = isset($device['_lastInform']) ? strtotime($device['_lastInform']) : 0;
                    $isOnline = (time() - $lastInform) < 300; // 5 mins
                    
                    return [
                        'online' => $isOnline,
                        'last_inform' => $device['_lastInform'] ?? null,
                        'raw' => $device,
                        'id' => $device['_id'] // Return ID for linking
                    ];
                }
            }
            
            return ['online' => false, 'error' => 'Device not found'];

        } catch (\Exception $e) {
            Log::error("GenieACS Error: " . $e->getMessage());
            return ['online' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Trigger Ping Diagnostics
     */
    public function ping($deviceId, $host)
    {
        $device = $this->getDeviceDetails($deviceId);
        if (!$device) return ['success' => false, 'message' => __('Device not found')];

        $params = [];
        $base = '';

        if (isset($device['InternetGatewayDevice'])) {
            // TR-098
            $base = 'InternetGatewayDevice.IPPingDiagnostics';
            $params["$base.DiagnosticsState"] = 'Requested';
            $params["$base.Host"] = $host;
            $params["$base.NumberOfRepetitions"] = 3;
        } elseif (isset($device['Device'])) {
            // TR-181
            $base = 'Device.IP.Diagnostics.IPPing';
            $params["$base.DiagnosticsState"] = 'Requested';
            $params["$base.Host"] = $host;
            $params["$base.NumberOfRepetitions"] = 3;
        } else {
            return ['success' => false, 'message' => __('Unknown device model')];
        }

        if ($this->setParameterValues($deviceId, $params)) {
            // We successfully requested ping.
            // In a real scenario, we'd need to poll for results (SuccessCount, FailureCount, etc.)
            // For now, we'll return success and the user might need to refresh to see results 
            // if we displayed the diagnostics parameters.
            return ['success' => true, 'message' => __('Ping requested. Check device logs/diagnostics for results.')];
        }

        return ['success' => false, 'message' => __('Failed to request ping')];
    }

    /**
     * Flatten device parameters for display
     */
    public function flattenParameters($device, $prefix = '', &$result = [])
    {
        foreach ($device as $key => $value) {
            if (str_starts_with($key, '_')) continue; // Skip metadata

            $currentKey = $prefix ? "$prefix.$key" : $key;

            if (is_array($value) && !isset($value['_value'])) {
                // It's a node, recurse
                $this->flattenParameters($value, $currentKey, $result);
            } else {
                // It's a leaf
                $val = is_array($value) ? ($value['_value'] ?? '') : $value;
                $writable = is_array($value) ? ($value['_writable'] ?? false) : false;
                $result[] = [
                    'path' => $currentKey,
                    'value' => $val,
                    'writable' => $writable
                ];
            }
        }
        return $result;
    }
}
