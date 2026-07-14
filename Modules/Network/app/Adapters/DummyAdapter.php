<?php

namespace Modules\Network\Adapters;

use Modules\Network\Contracts\NetworkProviderInterface;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class DummyAdapter implements NetworkProviderInterface
{
    protected array $customers = [];

    public function activateCustomer(Customer $customer): bool
    {
        Log::channel('network')->debug('[DummyAdapter] Activating customer', ['customer' => $customer->id]);
        $this->customers[$customer->pppoe_user] = [
            'status' => 'active',
            'profile' => $customer->package,
            'ip' => $customer->ip_address,
        ];
        return true;
    }

    public function suspendCustomer(Customer $customer): bool
    {
        Log::channel('network')->debug('[DummyAdapter] Suspending customer', ['customer' => $customer->id]);
        $this->customers[$customer->pppoe_user]['status'] = 'suspended';
        return true;
    }

    public function unsuspendCustomer(Customer $customer): bool
    {
        Log::channel('network')->debug('[DummyAdapter] Unsuspending customer', ['customer' => $customer->id]);
        $this->customers[$customer->pppoe_user]['status'] = 'active';
        return true;
    }

    public function disconnectCustomer(Customer $customer): bool
    {
        Log::channel('network')->debug('[DummyAdapter] Disconnecting customer', ['customer' => $customer->id]);
        return true;
    }

    public function reconnectCustomer(Customer $customer): bool
    {
        Log::channel('network')->debug('[DummyAdapter] Reconnecting customer', ['customer' => $customer->id]);
        return true;
    }

    public function changePassword(Customer $customer, string $newPassword): bool
    {
        Log::channel('network')->debug('[DummyAdapter] Changing password', ['customer' => $customer->id]);
        return true;
    }

    public function changeUsername(Customer $customer, string $newUsername): bool
    {
        Log::channel('network')->debug('[DummyAdapter] Changing username', ['customer' => $customer->id]);
        return true;
    }

    public function changeProfile(Customer $customer, string $newProfile): bool
    {
        Log::channel('network')->debug('[DummyAdapter] Changing profile', ['customer' => $customer->id, 'new_profile' => $newProfile]);
        if (isset($this->customers[$customer->pppoe_user])) {
            $this->customers[$customer->pppoe_user]['profile'] = $newProfile;
        }
        return true;
    }

    public function changeIPAddress(Customer $customer, string $newIp): bool
    {
        Log::channel('network')->debug('[DummyAdapter] Changing IP address', ['customer' => $customer->id, 'new_ip' => $newIp]);
        if (isset($this->customers[$customer->pppoe_user])) {
            $this->customers[$customer->pppoe_user]['ip'] = $newIp;
        }
        return true;
    }

    public function createCustomer(Customer $customer): bool
    {
        Log::channel('network')->debug('[DummyAdapter] Creating customer', ['customer' => $customer->id]);
        $this->customers[$customer->pppoe_user] = [
            'status' => 'active',
            'profile' => $customer->package,
            'ip' => $customer->ip_address,
        ];
        return true;
    }

    public function deleteCustomer(Customer $customer): bool
    {
        Log::channel('network')->debug('[DummyAdapter] Deleting customer', ['customer' => $customer->id]);
        unset($this->customers[$customer->pppoe_user]);
        return true;
    }

    public function customerExists(Customer $customer): bool
    {
        return isset($this->customers[$customer->pppoe_user]);
    }

    public function health(): array
    {
        return [
            'status' => 'ok',
            'checked_at' => now()->toIso8601String(),
        ];
    }

    public function ping(): bool
    {
        return true;
    }

    public function syncCustomer(Customer $customer): bool
    {
        Log::channel('network')->debug('[DummyAdapter] Syncing customer', ['customer' => $customer->id]);
        $this->customers[$customer->pppoe_user] = [
            'status' => $customer->status === 'active' ? 'active' : 'suspended',
            'profile' => $customer->package,
            'ip' => $customer->ip_address,
        ];
        return true;
    }

    public function syncAll(): int
    {
        Log::channel('network')->debug('[DummyAdapter] Syncing all customers');
        return count($this->customers);
    }
}
