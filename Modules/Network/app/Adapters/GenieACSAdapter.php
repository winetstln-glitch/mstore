<?php

namespace Modules\Network\Adapters;

use Modules\Network\Contracts\NetworkProviderInterface;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenieACSAdapter implements NetworkProviderInterface
{
    protected string $baseUrl;

    public function __construct()
    {
        $config = config('network.providers.genieacs', []);
        $this->baseUrl = rtrim(trim((string) ($config['base_url'] ?? 'http://localhost:7557')), '/');
    }

    public function activateCustomer(Customer $customer): bool
    {
        $deviceId = $this->getDeviceId($customer);
        if (! $deviceId) {
            return false;
        }
        // Set device to active
        return $this->setDeviceParameter($deviceId, 'InternetGatewayDevice.DeviceInfo.X_00E04C_Active', 'true');
    }

    public function suspendCustomer(Customer $customer): bool
    {
        $deviceId = $this->getDeviceId($customer);
        if (! $deviceId) {
            return false;
        }
        // Set device to inactive
        return $this->setDeviceParameter($deviceId, 'InternetGatewayDevice.DeviceInfo.X_00E04C_Active', 'false');
    }

    public function unsuspendCustomer(Customer $customer): bool
    {
        return $this->activateCustomer($customer);
    }

    public function disconnectCustomer(Customer $customer): bool
    {
        $deviceId = $this->getDeviceId($customer);
        if (! $deviceId) {
            return false;
        }
        // Reboot device as disconnect
        return $this->rebootDevice($deviceId);
    }

    public function reconnectCustomer(Customer $customer): bool
    {
        return $this->activateCustomer($customer);
    }

    public function changePassword(Customer $customer, string $newPassword): bool
    {
        $deviceId = $this->getDeviceId($customer);
        if (! $deviceId) {
            return false;
        }
        // Example: Set PPPoE password
        return $this->setDeviceParameter($deviceId, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Password', $newPassword);
    }

    public function changeUsername(Customer $customer, string $newUsername): bool
    {
        $deviceId = $this->getDeviceId($customer);
        if (! $deviceId) {
            return false;
        }
        // Example: Set PPPoE username
        return $this->setDeviceParameter($deviceId, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username', $newUsername);
    }

    public function changeProfile(Customer $customer, string $newProfile): bool
    {
        $deviceId = $this->getDeviceId($customer);
        if (! $deviceId) {
            return false;
        }
        // Apply provision preset
        return $this->applyProvision($deviceId, $newProfile);
    }

    public function changeIPAddress(Customer $customer, string $newIp): bool
    {
        $deviceId = $this->getDeviceId($customer);
        if (! $deviceId) {
            return false;
        }
        return $this->setDeviceParameter($deviceId, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress', $newIp);
    }

    public function createCustomer(Customer $customer): bool
    {
        // Create device (if using registration)
        return true;
    }

    public function deleteCustomer(Customer $customer): bool
    {
        $deviceId = $this->getDeviceId($customer);
        if (! $deviceId) {
            return false;
        }
        return $this->deleteDevice($deviceId);
    }

    public function customerExists(Customer $customer): bool
    {
        $deviceId = $this->getDeviceId($customer);
        if (! $deviceId) {
            return false;
        }
        return $this->deviceExists($deviceId);
    }

    public function health(): array
    {
        $checkedAt = now()->toIso8601String();
        $ok = $this->ping();
        return [
            'ok' => $ok,
            'checked_at' => $checkedAt,
        ];
    }

    public function ping(): bool
    {
        if (empty($this->baseUrl)) {
            return false;
        }
        try {
            $url = $this->baseUrl . '/';
            $resp = Http::timeout(5)->get($url);
            return $resp->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function syncCustomer(Customer $customer): bool
    {
        $deviceId = $this->getDeviceId($customer);
        if (! $deviceId) {
            return false;
        }
        return $this->refreshDevice($deviceId);
    }

    public function syncAll(): int
    {
        // Refresh all devices
        $devices = $this->getAllDevices();
        $count = 0;
        foreach ($devices as $device) {
            if ($this->refreshDevice($device['_id'])) {
                $count++;
            }
        }
        return $count;
    }

    protected function getDeviceId(Customer $customer): ?string
    {
        // Example: Use customer's device serial number or MAC
        return $customer->device_serial ?? $customer->device_mac ?? null;
    }

    protected function setDeviceParameter(string $deviceId, string $param, string $value): bool
    {
        try {
            $url = $this->baseUrl . '/devices/' . urlencode($deviceId) . '/tasks';
            $resp = Http::timeout(10)->acceptJson()->post($url, [
                'name' => 'setParameterValues',
                'parameterValues' => [[
                    'name' => $param,
                    'value' => $value,
                    'type' => 'xsd:string',
                ]],
            ]);
            return $resp->successful();
        } catch (\Throwable $e) {
            Log::error('GenieACSAdapter: setDeviceParameter failed', [
                'device_id' => $deviceId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function rebootDevice(string $deviceId): bool
    {
        try {
            $url = $this->baseUrl . '/devices/' . urlencode($deviceId) . '/tasks';
            $resp = Http::timeout(10)->acceptJson()->post($url, [
                'name' => 'reboot',
            ]);
            return $resp->successful();
        } catch (\Throwable $e) {
            Log::error('GenieACSAdapter: rebootDevice failed', [
                'device_id' => $deviceId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function applyProvision(string $deviceId, string $provisionName): bool
    {
        try {
            $url = $this->baseUrl . '/devices/' . urlencode($deviceId) . '/tasks';
            $resp = Http::timeout(10)->acceptJson()->post($url, [
                'name' => 'provision',
                'provision' => $provisionName,
            ]);
            return $resp->successful();
        } catch (\Throwable $e) {
            Log::error('GenieACSAdapter: applyProvision failed', [
                'device_id' => $deviceId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function deleteDevice(string $deviceId): bool
    {
        try {
            $url = $this->baseUrl . '/devices/' . urlencode($deviceId);
            $resp = Http::timeout(10)->delete($url);
            return $resp->successful();
        } catch (\Throwable $e) {
            Log::error('GenieACSAdapter: deleteDevice failed', [
                'device_id' => $deviceId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function deviceExists(string $deviceId): bool
    {
        try {
            $url = $this->baseUrl . '/devices/' . urlencode($deviceId);
            $resp = Http::timeout(10)->acceptJson()->get($url);
            return $resp->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function refreshDevice(string $deviceId): bool
    {
        try {
            $url = $this->baseUrl . '/devices/' . urlencode($deviceId) . '/tasks';
            $resp = Http::timeout(10)->acceptJson()->post($url, [
                'name' => 'refreshObject',
                'objectName' => '.',
            ]);
            return $resp->successful();
        } catch (\Throwable $e) {
            Log::error('GenieACSAdapter: refreshDevice failed', [
                'device_id' => $deviceId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function getAllDevices(): array
    {
        try {
            $url = $this->baseUrl . '/devices';
            $resp = Http::timeout(10)->acceptJson()->get($url);
            if ($resp->successful()) {
                return $resp->json() ?? [];
            }
            return [];
        } catch (\Throwable $e) {
            Log::error('GenieACSAdapter: getAllDevices failed', [
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
