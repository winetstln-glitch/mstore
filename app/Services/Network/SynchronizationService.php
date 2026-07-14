<?php

namespace App\Services\Network;

use App\Contracts\Network\NetworkProviderInterface;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class SynchronizationService
{
    public function __construct(
        protected NetworkProviderInterface $provider
    ) {}

    /**
     * Sync a single customer
     */
    public function syncCustomer(Customer $customer): bool
    {
        try {
            $success = $this->provider->syncCustomer($customer);
            if ($success) {
                Log::info('Customer synced successfully', ['customer_id' => $customer->id]);
            }
            return $success;
        } catch (\Exception $e) {
            Log::error('Customer sync failed: ' . $e->getMessage(), ['customer_id' => $customer->id]);
            return false;
        }
    }

    /**
     * Sync all customers
     */
    public function syncAll(): int
    {
        try {
            $count = $this->provider->syncAll();
            Log::info("Synced {$count} customers");
            return $count;
        } catch (\Exception $e) {
            Log::error('Sync all failed: ' . $e->getMessage());
            return 0;
        }
    }
}