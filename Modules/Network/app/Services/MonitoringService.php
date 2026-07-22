<?php

namespace Modules\Network\Services;

use Modules\Network\Contracts\NetworkProviderInterface;
use Modules\Network\Adapters\MikroTikAdapter;
use App\Models\Customer;
use App\Models\Router;
use App\Models\OLT;
use App\Services\Olt\OLTPollService;
use App\Services\Olt\OltService;
use App\Services\Olt\OLTFactory;
use Illuminate\Support\Facades\Log;

class MonitoringService
{
    public function __construct(
        protected NetworkProviderInterface $networkProvider,
        protected OLTPollService $oltPollService,
        protected OltService $oltService,
        protected OLTFactory $oltFactory
    ) {}

    public function health(): array
    {
        try {
            Log::channel('network')->debug('[MonitoringService] Checking health');

            return $this->networkProvider->health();
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to check health', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'checked_at' => now()->toIso8601String(),
            ];
        }
    }

    public function ping(): bool
    {
        try {
            Log::channel('network')->debug('[MonitoringService] Pinging');

            return $this->networkProvider->ping();
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to ping', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function isCustomerOnline(Customer $customer): bool
    {
        // For now, this is a placeholder
        // Real implementation would check via the adapter
        return false;
    }

    public function isRouterConnected(Router $router): bool
    {
        try {
            // Since we're dealing with a specific router, we'll use MikroTikAdapter directly
            $adapter = new MikroTikAdapter();
            return $adapter->isRouterConnected($router);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to check router connection', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function pollOlt(int $oltId): array
    {
        try {
            Log::channel('network')->debug('[MonitoringService] Polling OLT', ['olt_id' => $oltId]);
            return $this->oltPollService->poll($oltId);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to poll OLT', [
                'olt_id' => $oltId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'status' => 'error',
                'duration_ms' => 0,
                'onts_found' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getOltDriver(OLT $olt)
    {
        return $this->oltService->getDriver($olt);
    }

    public function getSystemResource(Router $router): array
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->getSystemResource($router);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get system resource', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function getPppoeActiveCount(Router $router): int
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->getPppoeActiveCount($router);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get PPPoE active count', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 0;
        }
    }

    public function getHotspotActiveCount(Router $router): int
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->getHotspotActiveCount($router);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get hotspot active count', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 0;
        }
    }

    public function getPppoeActiveList(Router $router): array
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->getPppoeActiveList($router);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get PPPoE active list', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function getHotspotActiveList(Router $router): array
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->getHotspotActiveList($router);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get hotspot active list', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function getSecrets(Router $router): array
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->getSecrets($router);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get secrets', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function getProfiles(Router $router): array
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->getProfiles($router);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get profiles', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function getInterfacesTrafficSnapshot(Router $router, int $seconds): array
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->getInterfacesTrafficSnapshot($router, $seconds);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get interfaces traffic snapshot', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function killActive(Router $router, string $name): bool
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->killActive($router, $name);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to kill active session', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function toggleSecret(Router $router, string $name, bool $enable): bool
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->toggleSecret($router, $name, $enable);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to toggle secret', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function disconnectHotspotById(Router $router, string $id): bool
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->disconnectHotspotById($router, $id);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to disconnect hotspot by ID', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function testOltConnection(array $oltData): bool
    {
        try {
            $driver = $this->oltFactory->createFromArray($oltData);
            return $driver->testConnection();
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to test OLT connection', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function getOltDeviceInfo(OLT $olt): array
    {
        try {
            $driver = $this->oltService->getDriver($olt);
            return $driver->getDeviceInfo();
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get OLT device info', [
                'olt_id' => $olt->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function getOltSystemResources(OLT $olt): array
    {
        try {
            $driver = $this->oltService->getDriver($olt);
            return $driver->getSystemResources();
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get OLT system resources', [
                'olt_id' => $olt->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function getOltPorts(OLT $olt): array
    {
        try {
            $driver = $this->oltService->getDriver($olt);
            return $driver->getPorts();
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get OLT ports', [
                'olt_id' => $olt->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function createOltDriverFromArray(array $oltData)
    {
        return $this->oltFactory->createFromArray($oltData);
    }

    public function setOnuName(OLT $olt, string $onuIndex, string $name): void
    {
        try {
            $driver = $this->oltService->getDriver($olt);
            $driver->connect($olt);
            $driver->setOnuName($onuIndex, $name);
            $driver->disconnect();
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to set ONU name', [
                'olt_id' => $olt->id,
                'onu_index' => $onuIndex,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function rebootOnu(OLT $olt, string $interface): void
    {
        try {
            $driver = $this->oltService->getDriver($olt);
            $driver->connect($olt);
            if ($olt->brand === 'hsgq') {
                $driver->rebootOnu($interface);
            }
            $driver->disconnect();
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to reboot ONU', [
                'olt_id' => $olt->id,
                'interface' => $interface,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getOltOnus(OLT $olt): array
    {
        try {
            $driver = $this->oltService->getDriver($olt);
            $driver->connect($olt, 30);
            $onus = $driver->getOnus();
            $driver->disconnect();
            return $onus;
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get OLT ONUs', [
                'olt_id' => $olt->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function createHotspotUser(Router $router, string $username, string $password, string $profile, ?string $limitUptime, ?int $limitBytesTotal): bool
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->createHotspotUser($router, $username, $password, $profile, $limitUptime, $limitBytesTotal);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to create hotspot user', [
                'router_id' => $router->id,
                'username' => $username,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    // ==================== Simple Queue Management ====================
    public function getSimpleQueues(Router $router): array
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->getSimpleQueues($router);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to get simple queues', [
                'router_id' => $router->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function createSimpleQueue(Router $router, array $data): bool
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->createSimpleQueue($router, $data);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to create simple queue', [
                'router_id' => $router->id,
                'data' => $data,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function updateSimpleQueue(Router $router, string $id, array $data): bool
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->updateSimpleQueue($router, $id, $data);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to update simple queue', [
                'router_id' => $router->id,
                'id' => $id,
                'data' => $data,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function deleteSimpleQueue(Router $router, string $id): bool
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->deleteSimpleQueue($router, $id);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to delete simple queue', [
                'router_id' => $router->id,
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function enableSimpleQueue(Router $router, string $id): bool
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->enableSimpleQueue($router, $id);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to enable simple queue', [
                'router_id' => $router->id,
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function disableSimpleQueue(Router $router, string $id): bool
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->disableSimpleQueue($router, $id);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to disable simple queue', [
                'router_id' => $router->id,
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function moveSimpleQueue(Router $router, string $id, ?string $destinationId = null): bool
    {
        try {
            $adapter = new MikroTikAdapter();
            return $adapter->moveSimpleQueue($router, $id, $destinationId);
        } catch (\Exception $e) {
            Log::channel('network')->error('[MonitoringService] Failed to move simple queue', [
                'router_id' => $router->id,
                'id' => $id,
                'destination_id' => $destinationId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}

