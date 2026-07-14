<?php

namespace Modules\Network\Listeners;

use App\Models\AuditLog;
use Modules\Network\Events\CustomerActivated;
use Modules\Network\Events\CustomerCreated;
use Modules\Network\Events\CustomerSuspended;
use Modules\Network\Events\CustomerUnsuspended;
use Modules\Network\Events\ProvisioningRequested;
use Modules\Network\Events\ProfileChanged;
use Illuminate\Support\Facades\Log;

class AuditLogListener
{
    public function handleCustomerCreated(CustomerCreated $event): void
    {
        try {
            AuditLog::log('created', $event->customer, [], $event->customer->toArray(), 'Network customer created');
        } catch (\Exception $e) {
            Log::channel('network')->error('[AuditLogListener] Failed to log CustomerCreated event', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function handleCustomerActivated(CustomerActivated $event): void
    {
        try {
            AuditLog::log('activated', $event->customer, $event->customer->getOriginal(), $event->customer->toArray(), 'Network customer activated');
        } catch (\Exception $e) {
            Log::channel('network')->error('[AuditLogListener] Failed to log CustomerActivated event', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function handleCustomerSuspended(CustomerSuspended $event): void
    {
        try {
            AuditLog::log('suspended', $event->customer, $event->customer->getOriginal(), $event->customer->toArray(), 'Network customer suspended');
        } catch (\Exception $e) {
            Log::channel('network')->error('[AuditLogListener] Failed to log CustomerSuspended event', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function handleCustomerUnsuspended(CustomerUnsuspended $event): void
    {
        try {
            AuditLog::log('unsuspended', $event->customer, $event->customer->getOriginal(), $event->customer->toArray(), 'Network customer unsuspended');
        } catch (\Exception $e) {
            Log::channel('network')->error('[AuditLogListener] Failed to log CustomerUnsuspended event', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function handleProvisioningRequested(ProvisioningRequested $event): void
    {
        try {
            Log::channel('network')->info('[AuditLogListener] Provisioning requested', [
                'customer_id' => $event->customer->id,
                'action' => $event->action,
            ]);
        } catch (\Exception $e) {
            Log::channel('network')->error('[AuditLogListener] Failed to log ProvisioningRequested event', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function handleProfileChanged(ProfileChanged $event): void
    {
        try {
            AuditLog::log('profile_changed', $event->customer, $event->customer->getOriginal(), $event->customer->toArray(), 'Network customer profile changed to '.$event->newProfile);
        } catch (\Exception $e) {
            Log::channel('network')->error('[AuditLogListener] Failed to log ProfileChanged event', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
