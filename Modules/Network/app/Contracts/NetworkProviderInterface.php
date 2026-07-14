<?php

namespace Modules\Network\Contracts;

use App\Models\Customer;

interface NetworkProviderInterface
{
    public function activateCustomer(Customer $customer): bool;

    public function suspendCustomer(Customer $customer): bool;

    public function unsuspendCustomer(Customer $customer): bool;

    public function disconnectCustomer(Customer $customer): bool;

    public function reconnectCustomer(Customer $customer): bool;

    public function changePassword(Customer $customer, string $newPassword): bool;

    public function changeUsername(Customer $customer, string $newUsername): bool;

    public function changeProfile(Customer $customer, string $newProfile): bool;

    public function changeIPAddress(Customer $customer, string $newIp): bool;

    public function createCustomer(Customer $customer): bool;

    public function deleteCustomer(Customer $customer): bool;

    public function customerExists(Customer $customer): bool;

    public function health(): array;

    public function ping(): bool;

    public function syncCustomer(Customer $customer): bool;

    public function syncAll(): int;
}
