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
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase',
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.PreSharedKey',
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations',
                'VirtualParameters.activedevices',
                'VirtualParameters.getdeviceuptime',
                'DeviceID.ProductClass',
                'Events.Registered',
                'Events.Inform',
                // Standard fallbacks
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.MACAddress',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.X_HW_VLAN',
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
     * Find device by Serial Number
     */
    public function findDeviceBySerial($serial)
    {
        try {
            // Projection for details needed in customer form
            $projection = implode(',', [
                '_id',
                '_deviceId._SerialNumber',
                '_deviceId._ProductClass',
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase',
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.PreSharedKey',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.MACAddress',
                'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.X_HW_VLAN',
                'Device.IP.Interface.1.IPv4Address.1.IPAddress',
                'Device.WiFi.SSID.1.SSID',
                'Device.WiFi.AccessPoint.1.Security.KeyPassphrase',
            ]);

            $query = json_encode(['_deviceId._SerialNumber' => $serial]);
            
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/devices", [
                    'query' => $query,
                    'projection' => $projection
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data) && is_array($data)) {
                    return $data[0];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error("GenieACS Find By Serial Error: " . $e->getMessage());
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
        $encodedId = urlencode($deviceId);

        // Attempt 1: Immediate execution (connection_request)
        try {
            // Use a short timeout (5s) for the immediate attempt
            $response = Http::timeout(5)
                ->post("{$this->baseUrl}/devices/{$encodedId}/tasks?timeout=3000&connection_request", [
                    'name' => 'refreshObject',
                    'objectName' => $objectName
                ]);

            if ($response->successful()) {
                return 2; // Immediate Success
            }
            Log::warning("GenieACS Refresh Immediate Failed: " . $response->body());
        } catch (\Exception $e) {
            Log::error("GenieACS Refresh Immediate Error: " . $e->getMessage());
        }

        // Attempt 2: Fallback to Queue
        try {
            Log::info("GenieACS: Retrying Refresh as Queued Task for $deviceId");
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$encodedId}/tasks", [
                    'name' => 'refreshObject',
                    'objectName' => $objectName
                ]);

            if ($response->successful()) {
                return 1; // Queued Success
            }
            Log::error("GenieACS Refresh Queue Failed: " . $response->body());
            
            // Check for cURL 52 (Empty Reply) which sometimes happens on success
            return 0;
        } catch (\Exception $e) {
            Log::error("GenieACS Refresh Queue Error: " . $e->getMessage());
            if (str_contains($e->getMessage(), 'cURL error 52')) {
                return 1; // Assumed Queued Success
            }
            return 0;
        }
    }

    /**
     * Reboot Device
     */
    public function rebootDevice($deviceId)
    {
        $encodedId = urlencode($deviceId);

        try {
            // Use a short timeout (5s) for the immediate attempt
            $response = Http::timeout(5)
                ->post("{$this->baseUrl}/devices/{$encodedId}/tasks?timeout=3000&connection_request", [
                    'name' => 'reboot'
                ]);

            if ($response->successful()) {
                return true;
            }
            Log::warning("GenieACS Reboot Immediate Failed: " . $response->body());
        } catch (\Exception $e) {
            Log::error("GenieACS Reboot Immediate Error: " . $e->getMessage());
        }

        try {
            Log::info("GenieACS: Retrying Reboot as Queued Task for $deviceId");

            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/devices/{$encodedId}/tasks", [
                    'name' => 'reboot'
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error("GenieACS Reboot Queue Failed: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("GenieACS Reboot Queue Error: " . $e->getMessage());
            $message = $e->getMessage();
            if (str_contains($message, 'cURL error 28') || str_contains($message, 'cURL error 52')) {
                Log::warning("GenieACS Reboot Queue assumed success after cURL timeout for $deviceId");
                return true;
            }
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
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_int($value)) {
                $value = (string) $value;
            }

            $parameterValues[] = [$key, (string) $value];
        }

        // Attempt 1: Immediate execution (connection_request)
        try {
            // Use a short timeout (5s) for the immediate attempt to avoid hanging if device is offline
            $response = Http::timeout(5)
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
            if (str_contains($e->getMessage(), 'cURL error 52')) {
                Log::warning("GenieACS SetParam Queue assumed success after cURL 52 for $deviceId");
                return true;
            }
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
            $wanBase = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1] ?? [];

            $base = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1';
            $connNode = $wanBase['WANIPConnection'][1] ?? [];
            
            // Check if PPP exists instead of IP
            if (isset($wanBase['WANPPPConnection'][1])) {
                $base = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1';
                $connNode = $wanBase['WANPPPConnection'][1];
            }

            $params["$base.Username"] = $username;
            $params["$base.Password"] = $password;
            if ($vlanId) {
                if (isset($connNode['X_BROADCOM_COM_VlanMuxID'])) {
                    $params["$base.X_BROADCOM_COM_VlanMuxID"] = $vlanId;
                }
                if (isset($connNode['X_CU_VLANEnabled'])) {
                    $params["$base.X_CU_VLANEnabled"] = 1;
                }
                if (isset($connNode['X_CU_VLAN'])) {
                    $params["$base.X_CU_VLAN"] = $vlanId;
                }
                if (isset($connNode['X_CMCC_VLANIDMark'])) {
                    $params["$base.X_CMCC_VLANIDMark"] = $vlanId;
                }
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
            $lan = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'] ?? [];

            // 2.4GHz (WLAN 1)
            if (isset($data['ssid_2g'])) {
                $base = 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1';
                $params["$base.SSID"] = $data['ssid_2g'];

                if (isset($data['password_2g'])) {
                    $wlan1 = $lan[1] ?? [];

                    if (isset($wlan1['PreSharedKey'][1]['KeyPassphrase'])) {
                        $params["$base.PreSharedKey.1.KeyPassphrase"] = $data['password_2g'];
                    } elseif (isset($wlan1['KeyPassphrase'])) {
                        $params["$base.KeyPassphrase"] = $data['password_2g'];
                    } else {
                        $params["$base.PreSharedKey.1.PreSharedKey"] = $data['password_2g'];
                    }
                }
            }

            // 5GHz (WLAN 2)
            if (isset($data['ssid_5g'])) {
                if (isset($device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][2])) {
                    $base = 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.2';
                    $params["$base.SSID"] = $data['ssid_5g'];

                    if (isset($data['password_5g'])) {
                        $wlan2 = $lan[2] ?? [];

                        if (isset($wlan2['PreSharedKey'][1]['KeyPassphrase'])) {
                            $params["$base.PreSharedKey.1.KeyPassphrase"] = $data['password_5g'];
                        } elseif (isset($wlan2['KeyPassphrase'])) {
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
                $config['wan_vlan'] = $this->getValue(
                    $conn['X_BROADCOM_COM_VlanMuxID']
                        ?? $conn['X_CU_VLAN']
                        ?? $conn['X_CMCC_VLANIDMark']
                        ?? ''
                );
            } elseif (isset($wanBase['WANIPConnection'][1])) {
                $conn = $wanBase['WANIPConnection'][1];
                $config['wan_user'] = $this->getValue($conn['Username'] ?? '');
                $config['wan_pass'] = $this->getValue($conn['Password'] ?? '');
                $config['wan_vlan'] = $this->getValue(
                    $conn['X_BROADCOM_COM_VlanMuxID']
                        ?? $conn['X_CU_VLAN']
                        ?? $conn['X_CMCC_VLANIDMark']
                        ?? ''
                );
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
     * Get Available WAN Connections (TR-098 & TR-181)
     */
    public function getWanConnections($deviceId, $device = null)
    {
        if (!$device) {
            $device = $this->getDeviceDetails($deviceId);
        }
        if (!$device) return [];

        $connections = [];
        
        // TR-098
        if (isset($device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1])) {
            $wanDev = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1];
            
            // Check IP Connections
            if (isset($wanDev['WANIPConnection'])) {
                foreach ($wanDev['WANIPConnection'] as $index => $conn) {
                    if (!is_numeric($index)) continue;
                    $name = $this->getValue($conn['Name'] ?? "wan_ip_$index");
                    $connections[] = [
                        'id' => "IP:$index",
                        'index' => $index,
                        'type' => 'IP',
                        'name' => $name,
                        'path' => "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.$index"
                    ];
                }
            }
            
            // Check PPP Connections
            if (isset($wanDev['WANPPPConnection'])) {
                foreach ($wanDev['WANPPPConnection'] as $index => $conn) {
                     if (!is_numeric($index)) continue;
                     $name = $this->getValue($conn['Name'] ?? "wan_ppp_$index");
                     $connections[] = [
                        'id' => "PPP:$index",
                        'index' => $index,
                        'type' => 'PPP',
                        'name' => $name,
                        'path' => "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.$index"
                    ];
                }
            }
        }
        // TR-181 (Basic Support)
        elseif (isset($device['Device']['IP']['Interface'])) {
            foreach ($device['Device']['IP']['Interface'] as $index => $conn) {
                if (!is_numeric($index)) continue;
                // Heuristic: Check if it looks like a WAN interface (e.g., has IPv4Address and is enabled)
                // or just list all for now.
                $name = $this->getValue($conn['Name'] ?? "ip_interface_$index");
                $alias = $this->getValue($conn['Alias'] ?? "");
                $displayName = $alias ?: $name;

                $connections[] = [
                    'id' => "IP:$index",
                    'index' => $index,
                    'type' => 'IP',
                    'name' => $displayName,
                    'path' => "Device.IP.Interface.$index"
                ];
            }
        }
        
        return $connections;
    }

    /**
     * Get WAN Settings for Advanced View
     */
    public function getWanSettings($deviceId, $path = null, $device = null)
    {
        if (!$device) {
            $device = $this->getDeviceDetails($deviceId);
        }
        if (!$device) return null;

        $settings = [
            'enable' => false,
            'conn_name' => '',
            'vlan' => '',
            'conn_type' => '',
            'service' => '',
            'username' => '',
            'password' => '',
            'nat' => false,
            'lan_bind' => '',
            'status' => '',
            'path' => '',
        ];

        // TR-098
        if (isset($device['InternetGatewayDevice'])) {
            $wanBase = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1] ?? [];
            
            $conn = null;
            
            if ($path && strpos($path, 'InternetGatewayDevice') === 0) {
                $parts = explode('.', $path);
                if (count($parts) >= 6) {
                    $type = $parts[4];
                    $index = $parts[5];
                    $conn = $wanBase[$type][$index] ?? null;
                    $settings['path'] = $path;
                }
            } else {
                // Default heuristic for TR-098
                $conn = $wanBase['WANIPConnection'][1] ?? null;
                $settings['path'] = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1';
                
                if (!$conn && isset($wanBase['WANPPPConnection'][1])) {
                    $conn = $wanBase['WANPPPConnection'][1];
                    $settings['path'] = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1';
                }
            }

            if ($conn) {
                $settings['enable'] = filter_var($this->getValue($conn['Enable'] ?? false), FILTER_VALIDATE_BOOLEAN);
                $settings['conn_name'] = $this->getValue($conn['Name'] ?? '');
                $settings['vlan'] = $this->getValue($conn['X_HW_VLAN'] ?? ($conn['X_BROADCOM_COM_VlanMuxID'] ?? ''));
                $settings['conn_type'] = $this->getValue($conn['ConnectionType'] ?? '');
                $settings['service'] = $this->getValue($conn['X_HW_ServiceList'] ?? '');
                $settings['username'] = $this->getValue($conn['Username'] ?? '');
                $settings['password'] = $this->getValue($conn['Password'] ?? ''); 
                $settings['nat'] = filter_var($this->getValue($conn['NATEnabled'] ?? false), FILTER_VALIDATE_BOOLEAN);
                $settings['lan_bind'] = $this->getValue($conn['X_HW_LANBinding'] ?? '');
                $settings['status'] = $this->getValue($conn['ConnectionStatus'] ?? '');
            }
        }
        // TR-181
        elseif (isset($device['Device'])) {
            $conn = null;
            if ($path && strpos($path, 'Device') === 0) {
                 // Path: Device.IP.Interface.1
                 $parts = explode('.', $path); // Device, IP, Interface, 1
                 if (count($parts) >= 4) {
                     $type = $parts[1]; // IP or PPP
                     $index = $parts[3];
                     $conn = $device['Device'][$type]['Interface'][$index] ?? null;
                     $settings['path'] = $path;
                 }
            } else {
                // Default heuristic for TR-181 (First IP Interface)
                 $conn = $device['Device']['IP']['Interface'][1] ?? null;
                 $settings['path'] = 'Device.IP.Interface.1';
            }

            if ($conn) {
                $settings['enable'] = filter_var($this->getValue($conn['Enable'] ?? false), FILTER_VALIDATE_BOOLEAN);
                $settings['conn_name'] = $this->getValue($conn['Alias'] ?? ($conn['Name'] ?? ''));
                $settings['status'] = $this->getValue($conn['Status'] ?? '');
                // Mapping TR-181 specific fields is complex as they differ from TR-098
                // For now, minimal support
                $settings['conn_type'] = 'IP'; // Simplified
            }
        }
        
        return $settings;
    }

    /**
     * Update WAN Advanced Settings
     */
    public function updateWanAdvanced($deviceId, $data, $path = null)
    {
        $device = $this->getDeviceDetails($deviceId);
        if (!$device) return false;

        $params = [];
        $base = '';

        if ($path) {
            $base = $path;
        } else {
            if (isset($device['InternetGatewayDevice'])) {
                 $wanBase = $device['InternetGatewayDevice']['WANDevice'][1]['WANConnectionDevice'][1] ?? [];
                 if (isset($wanBase['WANPPPConnection'][1])) {
                     $base = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1';
                 } else {
                     $base = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1';
                 }
            } else {
                return false;
            }
        }

        if (isset($data['enable'])) $params["$base.Enable"] = $data['enable'];
        if (isset($data['conn_name'])) $params["$base.Name"] = $data['conn_name'];
        if (isset($data['vlan'])) {
             $params["$base.X_HW_VLAN"] = $data['vlan'];
             $params["$base.X_BROADCOM_COM_VlanMuxID"] = $data['vlan'];
        }
        if (isset($data['conn_type'])) $params["$base.ConnectionType"] = $data['conn_type'];
        if (isset($data['service'])) $params["$base.X_HW_ServiceList"] = $data['service'];
        if (isset($data['username'])) $params["$base.Username"] = $data['username'];
        if (isset($data['password'])) $params["$base.Password"] = $data['password'];
        if (isset($data['nat'])) $params["$base.NATEnabled"] = $data['nat'];
        if (isset($data['lan_bind'])) $params["$base.X_HW_LANBinding"] = $data['lan_bind'];
        
        return $this->setParameterValues($deviceId, $params);
    }

    /**
     * Update WLAN Advanced Settings
     */
    public function updateWlanAdvanced($deviceId, $data, $index = 1)
    {
        $device = $this->getDeviceDetails($deviceId);
        if (!$device) return false;

        $params = [];
        
        // Check TR-098 (InternetGatewayDevice)
        if (isset($device['InternetGatewayDevice'])) {
            $base = "InternetGatewayDevice.LANDevice.1.WLANConfiguration.$index";
            $wlan = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][$index] ?? [];
            
            // Get Manufacturer for heuristics
            $manufacturer = $device['_deviceId']['_Manufacturer'] ?? '';
            if (empty($manufacturer) && isset($device['InternetGatewayDevice']['DeviceInfo']['Manufacturer'])) {
                 $manufacturer = $this->getValue($device['InternetGatewayDevice']['DeviceInfo']['Manufacturer']);
            }

            // Remove strict check: if (!$wlan) return false; 
            // We allow proceeding even if local data is missing, to support devices that haven't fully informed yet.

            if (isset($data['enable'])) $params["$base.Enable"] = $data['enable'];
            if (isset($data['ssid'])) $params["$base.SSID"] = $data['ssid'];
            
            if (isset($data['password'])) {
                if (isset($wlan['PreSharedKey'][1]['KeyPassphrase'])) {
                    $params["$base.PreSharedKey.1.KeyPassphrase"] = $data['password'];
                } elseif (isset($wlan['KeyPassphrase'])) {
                    $params["$base.KeyPassphrase"] = $data['password'];
                } else {
                    // Fallback heuristics based on manufacturer
                    if (stripos($manufacturer, 'Huawei') !== false) {
                         // Huawei usually prefers KeyPassphrase for WPA2
                         $params["$base.KeyPassphrase"] = $data['password'];
                    } else {
                         // Fallback default for ZTE/Others
                         $params["$base.PreSharedKey.1.PreSharedKey"] = $data['password'];
                    }
                }
            }

            if (isset($data['security'])) $params["$base.BeaconType"] = $data['security'];
            if (isset($data['channel'])) $params["$base.Channel"] = $data['channel'];
            if (isset($data['auto_channel'])) $params["$base.AutoChannelEnable"] = $data['auto_channel'];
            if (isset($data['power'])) $params["$base.TransmitPower"] = $data['power'];
        } 
        // Check TR-181 (Device)
        elseif (isset($device['Device'])) {
            $baseSSID = "Device.WiFi.SSID.$index";
            $baseAP = "Device.WiFi.AccessPoint.$index";
            
            if (isset($data['enable'])) $params["$baseSSID.Enable"] = $data['enable'];
            if (isset($data['ssid'])) $params["$baseSSID.SSID"] = $data['ssid'];
            
            if (isset($data['password'])) {
                $params["$baseAP.Security.KeyPassphrase"] = $data['password'];
            }
            
            if (isset($data['channel'])) $params["Device.WiFi.Radio.1.Channel"] = $data['channel']; // Simplified, usually Radio 1 is 2.4G
        }

        return $this->setParameterValues($deviceId, $params);
    }

    /**
     * Get WLAN Settings for Advanced View (2.4GHz)
     * Supports multiple SSIDs (index 1-4)
     */
    public function getWlanSettings($deviceId, $index = 1, $device = null)
    {
        if (!$device) {
            $device = $this->getDeviceDetails($deviceId);
        }
        if (!$device) return null;

        $settings = [
            'enable' => false,
            'ssid' => '',
            'password' => '',
            'security' => '', // BeaconType
            'bssid' => '',
            'channel' => '',
            'connected_devices' => 0,
            'auto_channel' => false,
            'power' => '',
        ];

        // TR-098
        if (isset($device['InternetGatewayDevice'])) {
            $wlan = $device['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][$index] ?? [];
            
            if ($wlan) {
                $settings['enable'] = filter_var($this->getValue($wlan['Enable'] ?? false), FILTER_VALIDATE_BOOLEAN);
                $settings['ssid'] = $this->getValue($wlan['SSID'] ?? '');
                
                // Password
                if (isset($wlan['PreSharedKey'][1]['KeyPassphrase'])) {
                    $settings['password'] = $this->getValue($wlan['PreSharedKey'][1]['KeyPassphrase']);
                } elseif (isset($wlan['KeyPassphrase'])) {
                    $settings['password'] = $this->getValue($wlan['KeyPassphrase']);
                } else {
                    $settings['password'] = $this->getValue($wlan['PreSharedKey'][1]['PreSharedKey'] ?? '');
                }

                $settings['security'] = $this->getValue($wlan['BeaconType'] ?? '');
                $settings['bssid'] = $this->getValue($wlan['BSSID'] ?? '');
                $settings['channel'] = $this->getValue($wlan['Channel'] ?? '');
                $settings['connected_devices'] = $this->getValue($wlan['TotalAssociations'] ?? 0);
                $settings['auto_channel'] = filter_var($this->getValue($wlan['AutoChannelEnable'] ?? false), FILTER_VALIDATE_BOOLEAN);
                $settings['power'] = $this->getValue($wlan['TransmitPower'] ?? '');
            }
        }
        // TR-181
        elseif (isset($device['Device'])) {
             // SSID Info
             $ssidObj = $device['Device']['WiFi']['SSID'][$index] ?? [];
             // AccessPoint Info (Security)
             $apObj = $device['Device']['WiFi']['AccessPoint'][$index] ?? [];
             // Radio Info (Channel, Power) - Assuming Radio 1 is 2.4GHz
             $radioObj = $device['Device']['WiFi']['Radio'][1] ?? [];

             if ($ssidObj) {
                 $settings['enable'] = filter_var($this->getValue($ssidObj['Enable'] ?? false), FILTER_VALIDATE_BOOLEAN);
                 $settings['ssid'] = $this->getValue($ssidObj['SSID'] ?? '');
                 $settings['bssid'] = $this->getValue($ssidObj['BSSID'] ?? '');
             }

             if ($apObj) {
                 $settings['security'] = $this->getValue($apObj['Security']['ModeEnabled'] ?? '');
                 $settings['password'] = $this->getValue($apObj['Security']['KeyPassphrase'] ?? '');
                 $settings['connected_devices'] = $this->getValue($apObj['AssociatedDeviceNumberOfEntries'] ?? 0);
             }

             if ($radioObj) {
                 $settings['channel'] = $this->getValue($radioObj['Channel'] ?? '');
                 $settings['auto_channel'] = filter_var($this->getValue($radioObj['AutoChannelEnable'] ?? false), FILTER_VALIDATE_BOOLEAN);
                 $settings['power'] = $this->getValue($radioObj['TransmitPower'] ?? '');
             }
        }

        return $settings;
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
