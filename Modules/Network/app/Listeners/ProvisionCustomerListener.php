<?php

namespace Modules\Network\Listeners;

use Modules\Network\Events\Domain\CustomerActivated;
use Illuminate\Support\Facades\Log;

class ProvisionCustomerListener
{
    public function handle(CustomerActivated $event): void
    {
        try {
            Log::channel('network')->info('[ProvisionCustomerListener] Customer activated', [
                'customer_id' => $event->customer->id,
            ]);
        } catch (\Exception $e) {
            Log::channel('network')->error('[ProvisionCustomerListener] Failed', [
                'customer_id' => $event->customer->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
