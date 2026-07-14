<?php

namespace App\Services\Network;

use App\Contracts\Network\NetworkProviderInterface;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProvisioningService
{
    public function __construct(
        protected NetworkProviderInterface $provider
    ) {}

    /**
     * Provision a new customer
     */
    public function provisionCustomer(Customer $customer): bool
    {
        return DB::transaction(function () use ($customer) {
            try {
                $success = $this->provider->activateCustomer($customer);
                if ($success) {
                    Log::info('Customer provisioned successfully', ['customer_id' => $customer->id]);
                }
                return $success;
            } catch (\Exception $e) {
                Log::error('Customer provisioning failed: ' . $e->getMessage(), ['customer_id' => $customer->id]);
                throw $e;
            }
        });
    }

    /**
     * Activate a customer
     */
    public function activateCustomer(Customer $customer): bool
    {
        return $this->provider->activateCustomer($customer);
    }

    /**
     * Suspend a customer
     */
    public function suspendCustomer(Customer $customer): bool
    {
        return $this->provider->suspendCustomer($customer);
    }

    /**
     * Disconnect a customer
     */
    public function disconnectCustomer(Customer $customer): bool
    {
        return $this->provider->disconnectCustomer($customer);
    }

    /**
     * Reconnect a customer
     */
    public function reconnectCustomer(Customer $customer): bool
    {
        return $this->provider->reconnectCustomer($customer);
    }

    /**
     * Change customer password
     */
    public function changePassword(Customer $customer, string $newPassword): bool
    {
        return $this->provider->changePassword($customer, $newPassword);
    }

    /**
     * Change customer service profile
     */
    public function changeProfile(Customer $customer, string $newProfile): bool
    {
        return $this->provider->changeProfile($customer, $newProfile);
    }

    /**
     * Change customer IP address
     */
    public function changeIPAddress(Customer $customer, string $newIp): bool
    {
        return $this->provider->changeIPAddress($customer, $newIp);
    }
}