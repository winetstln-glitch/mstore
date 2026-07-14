<?php

namespace Modules\Network\Listeners;

use Modules\Network\Events\Domain\CustomerUnsuspended;
use Illuminate\Support\Facades\Log;

class UnsuspendCustomerListener
{
    public function handle(CustomerUnsuspended $event): void
    {
        try {
            Log::channel('network')->info('[UnsuspendCustomerListener] Customer unsuspended', [
                'customer_id' => $event->customer->id,
            ]);
        } catch (\Exception $e) {
            Log::channel('network')->error('[UnsuspendCustomerListener] Failed', [
                'customer_id' => $event->customer->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
