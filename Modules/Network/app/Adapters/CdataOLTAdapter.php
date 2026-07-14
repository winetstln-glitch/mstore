<?php

namespace Modules\Network\Adapters;

use Modules\Network\Contracts\NetworkProviderInterface;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class CdataOLTAdapter implements NetworkProviderInterface
{
    protected string $host;

    protected int $port;

    protected string $username;

    protected string $password;

    public function __construct()
    {
        $config = config('network.providers.cdataolt', []);
        $this->host = $config['host'] ?? '';
        $this->port = $config['port'] ?? 22;
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
    }

    public function activateCustomer(Customer $customer): bool
    {
        $ontId = $this->getOntId($customer);
        if (! $ontId) {
            return false;
        }
        return $this->executeOltCommand("enable ont $ontId");
    }

    public function suspendCustomer(Customer $customer): bool
    {
        $ontId = $this->getOntId($customer);
        if (! $ontId) {
            return false;
        }
        return $this->executeOltCommand("disable ont $ontId");
    }

    public function unsuspendCustomer(Customer $customer): bool
    {
        return $this->activateCustomer($customer);
    }

    public function disconnectCustomer(Customer $customer): bool
    {
        return $this->suspendCustomer($customer);
    }

    public function reconnectCustomer(Customer $customer): bool
    {
        return $this->activateCustomer($customer);
    }

    public function changePassword(Customer $customer, string $newPassword): bool
    {
        $ontId = $this->getOntId($customer);
        if (! $ontId) {
            return false;
        }
        return $this->executeOltCommand("set ont $ontId pppoe password $newPassword");
    }

    public function changeUsername(Customer $customer, string $newUsername): bool
    {
        $ontId = $this->getOntId($customer);
        if (! $ontId) {
            return false;
        }
        return $this->executeOltCommand("set ont $ontId pppoe username $newUsername");
    }

    public function changeProfile(Customer $customer, string $newProfile): bool
    {
        $ontId = $this->getOntId($customer);
        if (! $ontId) {
            return false;
        }
        return $this->executeOltCommand("set ont $ontId profile $newProfile");
    }

    public function changeIPAddress(Customer $customer, string $newIp): bool
    {
        $ontId = $this->getOntId($customer);
        if (! $ontId) {
            return false;
        }
        return $this->executeOltCommand("set ont $ontId ip $newIp");
    }

    public function createCustomer(Customer $customer): bool
    {
        $ontId = $this->getOntId($customer);
        if (! $ontId) {
            return false;
        }
        return $this->executeOltCommand("register ont $ontId");
    }

    public function deleteCustomer(Customer $customer): bool
    {
        $ontId = $this->getOntId($customer);
        if (! $ontId) {
            return false;
        }
        return $this->executeOltCommand("unregister ont $ontId");
    }

    public function customerExists(Customer $customer): bool
    {
        $ontId = $this->getOntId($customer);
        if (! $ontId) {
            return false;
        }
        $result = $this->executeOltCommand("show ont $ontId");
        return strpos($result, 'Found') !== false;
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
        if (empty($this->host)) {
            return false;
        }
        $output = [];
        $resultCode = 0;
        exec("ping -n 1 -w 1000 " . escapeshellarg($this->host), $output, $resultCode);
        return $resultCode === 0;
    }

    public function syncCustomer(Customer $customer): bool
    {
        $ontId = $this->getOntId($customer);
        if (! $ontId) {
            return false;
        }
        return $this->executeOltCommand("sync ont $ontId") !== false;
    }

    public function syncAll(): int
    {
        $result = $this->executeOltCommand("show ont all");
        preg_match_all('/ont_id=(\d+)/', $result, $matches);
        $count = 0;
        foreach ($matches[1] ?? [] as $ontId) {
            if ($this->executeOltCommand("sync ont $ontId")) {
                $count++;
            }
        }
        return $count;
    }

    protected function getOntId(Customer $customer): ?string
    {
        return $customer->ont_id ?? $customer->device_id ?? null;
    }

    protected function executeOltCommand(string $command): string|false
    {
        Log::warning('CdataOLTAdapter: executeOltCommand is a placeholder', ['command' => $command]);
        return false;
    }
}
