<?php

namespace Modules\Network\Adapters;

use Modules\Network\Contracts\NetworkProviderInterface;
use App\Models\Customer;
use App\Models\Router;
use Illuminate\Support\Facades\Log;
use Exception;
use RouterOS\Client;
use RouterOS\Query;

class MikroTikAdapter implements NetworkProviderInterface
{
    protected function getClient(Router $router): ?Client
    {
        try {
            $host = $router->vpn_tunnel_ip ?: $router->host;
            if (empty($host)) {
                Log::error('MikroTikAdapter: No host address for router', ['router_id' => $router->id]);
                return null;
            }

            return new Client([
                'host' => $host,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => (int) $router->port,
            ]);
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Failed to create client', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function isRouterConnected(Router $router): bool
    {
        try {
            $client = $this->getClient($router);
            return $client !== null;
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error checking router connection', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getSystemResource(Router $router): array
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return [];

            $query = new Query('/system/resource/print');
            $result = $client->query($query)->read();

            return !empty($result) ? $result[0] : [];
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error getting system resource', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getPppoeActiveCount(Router $router): int
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return 0;

            $query = new Query('/ppp/active/print');
            $query->where('service', 'pppoe');
            $result = $client->query($query)->read();

            return count($result);
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error getting PPPoE active count', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return 0;
        }
    }

    public function getHotspotActiveCount(Router $router): int
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return 0;

            $query = new Query('/ip/hotspot/active/print');
            $result = $client->query($query)->read();

            return count($result);
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error getting hotspot active count', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return 0;
        }
    }

    public function getPppoeActiveList(Router $router): array
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return [];

            $query = new Query('/ppp/active/print');
            $query->where('service', 'pppoe');
            return $client->query($query)->read();
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error getting PPPoE active list', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getHotspotActiveList(Router $router): array
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return [];

            $query = new Query('/ip/hotspot/active/print');
            return $client->query($query)->read();
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error getting hotspot active list', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getSecrets(Router $router): array
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return [];

            $query = new Query('/ppp/secret/print');
            return $client->query($query)->read();
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error getting secrets', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getProfiles(Router $router): array
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return [];

            $query = new Query('/ppp/profile/print');
            return $client->query($query)->read();
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error getting profiles', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    protected function getInterfaceTraffic(Client $client, string $interfaceName): ?array
    {
        try {
            $query = new Query('/interface/monitor-traffic');
            $query->equal('interface', $interfaceName);
            $query->equal('once', 'true');
            $result = $client->query($query)->read();

            return !empty($result) ? $result[0] : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getInterfacesTrafficSnapshot(Router $router, int $seconds = 4): array
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return [];

            $query = new Query('/interface/print');
            $interfaces = $client->query($query)->read();

            $result = [];
            $count = 0;

            foreach ($interfaces as $interface) {
                if (!is_array($interface) || empty($interface['name'])) {
                    continue;
                }

                if (isset($interface['disabled']) && $interface['disabled'] === 'true') {
                    continue;
                }

                $traffic = $this->getInterfaceTraffic($client, $interface['name']);

                $result[] = [
                    'name' => $interface['name'],
                    'rx' => isset($traffic['rx-bits-per-second']) ? (int) $traffic['rx-bits-per-second'] : 0,
                    'tx' => isset($traffic['tx-bits-per-second']) ? (int) $traffic['tx-bits-per-second'] : 0,
                ];

                $count++;
                if ($count >= $seconds) { // Wait, $seconds was used as limit in legacy service
                    break;
                }
            }

            return $result;
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error getting interfaces traffic snapshot', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function killActive(Router $router, string $name): bool
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return false;

            // Find active connection
            $query = new Query('/ppp/active/print');
            $query->where('name', $name);
            $active = $client->query($query)->read();

            foreach ($active as $conn) {
                $kill = new Query('/ppp/active/remove');
                $kill->equal('.id', $conn['.id']);
                $client->query($kill)->read();
            }

            return true;
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error killing active session', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function findSecretId(Client $client, string $name): ?string
    {
        $query = new Query('/ppp/secret/print');
        $query->where('name', $name);
        $secrets = $client->query($query)->read();

        if (empty($secrets)) {
            return null;
        }

        return $secrets[0]['.id'];
    }

    public function toggleSecret(Router $router, string $name, bool $enable): bool
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return false;

            $secretId = $this->findSecretId($client, $name);
            if (!$secretId) return false;

            $action = $enable ? 'enable' : 'disable';
            $query = new Query("/ppp/secret/$action");
            $query->equal('.id', $secretId);
            $client->query($query)->read();

            // If disabling, also kill active connection
            if (!$enable) {
                $this->killActive($router, $name);
            }

            return true;
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error toggling secret', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function disconnectHotspotById(Router $router, string $id): bool
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return false;

            $query = new Query('/ip/hotspot/active/remove');
            $query->equal('.id', $id);
            $client->query($query)->read();

            return true;
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error disconnecting hotspot by ID', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function createHotspotUser(Router $router, string $username, string $password, string $profile, ?string $limitUptime, ?int $limitBytesTotal): bool
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return false;

            $query = new Query('/ip/hotspot/user/add');
            $query->equal('name', $username);
            $query->equal('password', $password);
            $query->equal('profile', $profile);

            if ($limitUptime) {
                $query->equal('limit-uptime', $limitUptime);
            }
            if ($limitBytesTotal) {
                $query->equal('limit-bytes-total', $limitBytesTotal);
            }

            $client->query($query)->read();

            return true;
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error creating hotspot user', [
                'router_id' => $router->id,
                'username' => $username,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function activateCustomer(Customer $customer): bool
    {
        if (!$customer->router_id) {
            Log::warning('MikroTikAdapter: No router assigned to customer', ['customer_id' => $customer->id]);
            return false;
        }

        $router = $customer->router;
        if (!$router) {
            Log::warning('MikroTikAdapter: Router not found for customer', ['customer_id' => $customer->id, 'router_id' => $customer->router_id]);
            return false;
        }

        try {
            if (!$this->customerExists($customer)) {
                $this->createCustomer($customer);
            }
            return $this->toggleSecret($router, $customer->pppoe_user, true);
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error activating customer', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function suspendCustomer(Customer $customer): bool
    {
        if (!$customer->router_id) {
            Log::warning('MikroTikAdapter: No router assigned to customer', ['customer_id' => $customer->id]);
            return false;
        }

        $router = $customer->router;
        if (!$router) {
            Log::warning('MikroTikAdapter: Router not found for customer', ['customer_id' => $customer->id, 'router_id' => $customer->router_id]);
            return false;
        }

        try {
            return $this->toggleSecret($router, $customer->pppoe_user, false);
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error suspending customer', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function unsuspendCustomer(Customer $customer): bool
    {
        return $this->activateCustomer($customer);
    }

    public function disconnectCustomer(Customer $customer): bool
    {
        if (!$customer->router_id) {
            Log::warning('MikroTikAdapter: No router assigned to customer', ['customer_id' => $customer->id]);
            return false;
        }

        $router = $customer->router;
        if (!$router) {
            Log::warning('MikroTikAdapter: Router not found for customer', ['customer_id' => $customer->id, 'router_id' => $customer->router_id]);
            return false;
        }

        try {
            return $this->killActive($router, $customer->pppoe_user);
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error disconnecting customer', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function reconnectCustomer(Customer $customer): bool
    {
        $disconnected = $this->disconnectCustomer($customer);
        return $disconnected;
    }

    public function updateSecret(Router $router, string $oldName, array $data): bool
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return false;

            $secretId = $this->findSecretId($client, $oldName);
            if (!$secretId) return false;

            $query = new Query('/ppp/secret/set');
            $query->equal('.id', $secretId);

            foreach ($data as $key => $value) {
                $query->equal($key, $value);
            }

            $client->query($query)->read();

            return true;
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error updating secret', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function changePassword(Customer $customer, string $newPassword): bool
    {
        if (!$customer->router_id) {
            Log::warning('MikroTikAdapter: No router assigned to customer', ['customer_id' => $customer->id]);
            return false;
        }

        $router = $customer->router;
        if (!$router) {
            Log::warning('MikroTikAdapter: Router not found for customer', ['customer_id' => $customer->id, 'router_id' => $customer->router_id]);
            return false;
        }

        try {
            return $this->updateSecret($router, $customer->pppoe_user, ['password' => $newPassword]);
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error changing password', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function changeUsername(Customer $customer, string $newUsername): bool
    {
        if (!$customer->router_id) {
            Log::warning('MikroTikAdapter: No router assigned to customer', ['customer_id' => $customer->id]);
            return false;
        }

        $router = $customer->router;
        if (!$router) {
            Log::warning('MikroTikAdapter: Router not found for customer', ['customer_id' => $customer->id, 'router_id' => $customer->router_id]);
            return false;
        }

        try {
            return $this->updateSecret($router, $customer->pppoe_user, ['name' => $newUsername]);
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error changing username', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function changeProfile(Customer $customer, string $newProfile): bool
    {
        if (!$customer->router_id) {
            Log::warning('MikroTikAdapter: No router assigned to customer', ['customer_id' => $customer->id]);
            return false;
        }

        $router = $customer->router;
        if (!$router) {
            Log::warning('MikroTikAdapter: Router not found for customer', ['customer_id' => $customer->id, 'router_id' => $customer->router_id]);
            return false;
        }

        try {
            return $this->updateSecret($router, $customer->pppoe_user, ['profile' => $newProfile]);
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error changing profile', [
                'customer_id' => $customer->id,
                'new_profile' => $newProfile,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function changeIPAddress(Customer $customer, string $newIp): bool
    {
        if (!$customer->router_id) {
            Log::warning('MikroTikAdapter: No router assigned to customer', ['customer_id' => $customer->id]);
            return false;
        }

        $router = $customer->router;
        if (!$router) {
            Log::warning('MikroTikAdapter: Router not found for customer', ['customer_id' => $customer->id, 'router_id' => $customer->router_id]);
            return false;
        }

        try {
            return $this->updateSecret($router, $customer->pppoe_user, ['remote-address' => $newIp]);
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error changing IP address', [
                'customer_id' => $customer->id,
                'new_ip' => $newIp,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function createSecret(Router $router, string $name, string $password, string $profile = 'default', ?string $localAddress = null, ?string $remoteAddress = null, string $service = 'pppoe'): bool
    {
        try {
            $client = $this->getClient($router);
            if (!$client) return false;

            $query = new Query('/ppp/secret/add');
            $query->equal('name', $name);
            $query->equal('password', $password);
            $query->equal('profile', $profile);
            $query->equal('service', $service);

            if ($localAddress) {
                $query->equal('local-address', $localAddress);
            }
            if ($remoteAddress) {
                $query->equal('remote-address', $remoteAddress);
            }

            $client->query($query)->read();

            return true;
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error creating secret', [
                'router_id' => $router->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function createCustomer(Customer $customer): bool
    {
        if (!$customer->router_id) {
            Log::warning('MikroTikAdapter: No router assigned to customer', ['customer_id' => $customer->id]);
            return false;
        }

        $router = $customer->router;
        if (!$router) {
            Log::warning('MikroTikAdapter: Router not found for customer', ['customer_id' => $customer->id, 'router_id' => $customer->router_id]);
            return false;
        }

        if (empty($customer->pppoe_user) || empty($customer->pppoe_password)) {
            Log::warning('MikroTikAdapter: Missing PPPoE credentials for customer', ['customer_id' => $customer->id]);
            return false;
        }

        try {
            return $this->createSecret(
                $router,
                $customer->pppoe_user,
                $customer->pppoe_password,
                $customer->package ?? 'default',
                null,
                $customer->ip_address
            );
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error creating customer', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function deleteCustomer(Customer $customer): bool
    {
        if (!$customer->router_id) {
            Log::warning('MikroTikAdapter: No router assigned to customer', ['customer_id' => $customer->id]);
            return false;
        }

        $router = $customer->router;
        if (!$router) {
            Log::warning('MikroTikAdapter: Router not found for customer', ['customer_id' => $customer->id, 'router_id' => $customer->router_id]);
            return false;
        }

        try {
            $this->toggleSecret($router, $customer->pppoe_user, false);
            return true;
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error deleting customer', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function customerExists(Customer $customer): bool
    {
        if (!$customer->router_id) {
            Log::warning('MikroTikAdapter: No router assigned to customer', ['customer_id' => $customer->id]);
            return false;
        }

        $router = $customer->router;
        if (!$router) {
            Log::warning('MikroTikAdapter: Router not found for customer', ['customer_id' => $customer->id, 'router_id' => $customer->router_id]);
            return false;
        }

        try {
            $secrets = $this->getSecrets($router);
            foreach ($secrets as $secret) {
                if (isset($secret['name']) && $secret['name'] === $customer->pppoe_user) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error checking customer existence', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function health(): array
    {
        return [
            'status' => 'ok',
            'checked_at' => now()->toIso8601String()
        ];
    }

    public function ping(): bool
    {
        return true;
    }

    public function syncCustomer(Customer $customer): bool
    {
        if (!$customer->router_id) {
            Log::warning('MikroTikAdapter: No router assigned to customer', ['customer_id' => $customer->id]);
            return false;
        }

        $router = $customer->router;
        if (!$router) {
            Log::warning('MikroTikAdapter: Router not found for customer', ['customer_id' => $customer->id, 'router_id' => $customer->router_id]);
            return false;
        }

        try {
            if (!$this->customerExists($customer)) {
                return $this->createCustomer($customer);
            }

            return $this->updateSecret($router, $customer->pppoe_user, [
                'password' => $customer->pppoe_password,
                'profile' => $customer->package ?? 'default',
                'remote-address' => $customer->ip_address
            ]);
        } catch (Exception $e) {
            Log::error('MikroTikAdapter: Error syncing customer', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function syncAll(): int
    {
        $count = 0;
        $customers = Customer::whereNotNull('router_id')->whereNotNull('pppoe_user')->get();

        foreach ($customers as $customer) {
            if ($this->syncCustomer($customer)) {
                $count++;
            }
        }

        return $count;
    }
}
