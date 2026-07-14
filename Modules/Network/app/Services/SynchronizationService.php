<?php

namespace Modules\Network\Services;

use Modules\Network\Contracts\NetworkProviderInterface;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class SynchronizationService
{
    public function __construct(protected NetworkProviderInterface $networkProvider) {}

    public function syncCustomer(Customer $customer): bool
    {
        try {
            Log::channel('network')->info('[SynchronizationService] Syncing customer', [
                'customer_id' => $customer->id,
            ]);

            return $this->networkProvider->syncCustomer($customer);
        } catch (\Exception $e) {
            Log::channel('network')->error('[SynchronizationService] Failed to sync customer', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function syncAll(): int
    {
        try {
            Log::channel('network')->info('[SynchronizationService] Syncing all customers');

            return $this->networkProvider->syncAll();
        } catch (\Exception $e) {
            Log::channel('network')->error('[SynchronizationService] Failed to sync all customers', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 0;
        }
    }
}

