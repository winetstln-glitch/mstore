<?php

namespace App\Services\Network\Adapters;

use App\Contracts\Network\NetworkProviderInterface;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class DummyAdapter implements NetworkProviderInterface
{
    public function activateCustomer(Customer $customer): bool
    {
        Log::info('DummyAdapter: Activating customer', ['customer_id' => $customer->id]);
        return true;
    }

    public function suspendCustomer(Customer $customer): bool
    {
        Log::info('DummyAdapter: Suspending customer', ['customer_id' => $customer->id]);
        return true;
    }

    public function disconnectCustomer(Customer $customer): bool
    {
        Log::info('DummyAdapter: Disconnecting customer', ['customer_id' => $customer->id]);
        return true;
    }

    public function reconnectCustomer(Customer $customer): bool
    {
        Log::info('DummyAdapter: Reconnecting customer', ['customer_id' => $customer->id]);
        return true;
    }

    public function changePassword(Customer $customer, string $newPassword): bool
    {
        Log::info('DummyAdapter: Changing password', ['customer_id' => $customer->id]);
        return true;
    }

    public function changeProfile(Customer $customer, string $newProfile): bool
    {
        Log::info('DummyAdapter: Changing profile', ['customer_id' => $customer->id, 'profile' => $newProfile]);
        return true;
    }

    public function changeIPAddress(Customer $customer, string $newIp): bool
    {
        Log::info('DummyAdapter: Changing IP', ['customer_id' => $customer->id, 'ip' => $newIp]);
        return true;
    }

    public function verifyCredentials(string $username, string $password): array
    {
        Log::info('DummyAdapter: Verifying credentials', ['username' => $username]);
        return [
            'ok' => true,
            'message' => 'Dummy authentication successful'
        ];
    }

    public function isAvailable(): bool
    {
        Log::info('DummyAdapter: Checking availability');
        return true;
    }

    public function health(): array
    {
        Log::info('DummyAdapter: Checking health');
        return [
            'ok' => true,
            'ok_service' => true,
            'ok_auth' => true,
            'latency_ms' => 10,
            'checked_at' => now()->toIso8601String()
        ];
    }

    public function syncCustomer(Customer $customer): bool
    {
        Log::info('DummyAdapter: Syncing customer', ['customer_id' => $customer->id]);
        return true;
    }

    public function syncAll(): int
    {
        Log::info('DummyAdapter: Syncing all customers');
        return Customer::count();
    }
}