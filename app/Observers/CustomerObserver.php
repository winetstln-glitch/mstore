<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\GenieDeviceStatus;
use App\Services\GenieACSService;
use Illuminate\Support\Facades\Log;

class CustomerObserver
{
    protected $genieService;

    public function __construct(GenieACSService $genieService)
    {
        $this->genieService = $genieService;
    }

    /**
     * Handle the Customer "updated" event.
     */
    public function updated(Customer $customer): void
    {
        // If SN or Device ID changed, refresh status immediately
        if ($customer->isDirty('onu_serial') || $customer->isDirty('genieacs_device_id')) {
            $deviceId = $customer->genieacs_device_id ?: $customer->onu_serial;
            
            if ($deviceId) {
                try {
                    $status = $this->genieService->getDeviceStatus($deviceId);
                    
                    GenieDeviceStatus::updateOrCreate(
                        ['customer_id' => $customer->id],
                        [
                            'onu_serial' => $customer->onu_serial,
                            'is_online' => (bool)($status['online'] ?? false),
                            'last_inform' => $status['last_inform'] ?? null,
                            'tr069_ip' => $status['tr069_ip'] ?? null,
                            'connection_request_url' => $status['connection_request_url'] ?? null,
                        ]
                    );
                    
                    Log::info("GenieDeviceStatus refreshed for customer {$customer->id} after SN/ID update.");
                } catch (\Exception $e) {
                    Log::error("Failed to refresh status for customer {$customer->id}: " . $e->getMessage());
                }
            }
        }
    }
}
