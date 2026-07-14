<?php

namespace Modules\Network\Listeners;

use Modules\Network\Events\Domain\CustomerSuspended;
use Illuminate\Support\Facades\Log;

class SuspendCustomerListener
{
    public function handle(CustomerSuspended $event): void
    {
        try {
            Log::channel('network')->info('[SuspendCustomerListener] Customer suspended', [
                'customer_id' => $event->customer->id,
            ]);
        } catch (\Exception $e) {
            Log::channel('network')->error('[SuspendCustomerListener] Failed', [
                'customer_id' => $event->customer->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
