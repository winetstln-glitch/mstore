<?php

namespace Modules\Network\Listeners;

use Modules\Network\Events\CustomerCreated;
use Modules\Network\Services\SynchronizationService;
use Illuminate\Support\Facades\Log;

class SyncCustomerListener
{
    public function __construct(protected SynchronizationService $synchronizationService) {}

    public function handle(CustomerCreated $event): void
    {
        try {
            Log::channel('network')->info('[SyncCustomerListener] Handling CustomerCreated event', [
                'customer_id' => $event->customer->id,
            ]);

            $this->synchronizationService->syncCustomer($event->customer);
        } catch (\Exception $e) {
            Log::channel('network')->error('[SyncCustomerListener] Failed to handle CustomerCreated event', [
                'customer_id' => $event->customer->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
