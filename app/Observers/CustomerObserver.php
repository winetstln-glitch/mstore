<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\GenieDeviceStatus;
use App\Models\Htb;
use App\Models\Odp;
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
     * Handle the Customer "created" event.
     */
    public function created(Customer $customer): void
    {
        $this->updateOdpHtbFilled($customer, true);
        AuditLog::log('created', $customer, [], $customer->toArray(), 'Customer created');
        Log::info("Customer {$customer->id} created.");
    }

    /**
     * Handle the Customer "updated" event.
     */
    public function updated(Customer $customer): void
    {
        $this->updateOdpHtbFilled($customer, false);
        AuditLog::log('updated', $customer, $customer->getOriginal(), $customer->toArray(), 'Customer updated');

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

    /**
     * Handle the Customer "deleted" event.
     */
    public function deleted(Customer $customer): void
    {
        $this->updateOdpHtbFilled($customer, false, true);
        AuditLog::log('deleted', $customer, $customer->toArray(), [], 'Customer deleted');
        Log::info("Customer {$customer->id} deleted.");
    }

    /**
     * Update ODP and HTB filled count.
     */
    protected function updateOdpHtbFilled(Customer $customer, bool $isNew, bool $isDeleted = false): void
    {
        if ($isNew || $isDeleted) {
            if ($customer->htb_id) {
                $isDeleted ? Htb::where('id', $customer->htb_id)->decrement('filled') : Htb::where('id', $customer->htb_id)->increment('filled');
            } elseif ($customer->odp_id) {
                $isDeleted ? Odp::where('id', $customer->odp_id)->decrement('filled') : Odp::where('id', $customer->odp_id)->increment('filled');
            }
            return;
        }

        // Handle updates
        $oldHtbId = $customer->getOriginal('htb_id');
        $newHtbId = $customer->htb_id;
        $oldOdpId = $customer->getOriginal('odp_id');
        $newOdpId = $customer->odp_id;
        $oldIsHtb = ! is_null($oldHtbId);
        $newIsHtb = ! is_null($newHtbId);

        // Revert old counts
        if (! $oldIsHtb && $oldOdpId) {
            if ($customer->isDirty('odp_id') || $newIsHtb) {
                Odp::where('id', $oldOdpId)->decrement('filled');
            }
        } elseif ($oldIsHtb && $oldHtbId) {
            if ($customer->isDirty('htb_id') || ! $newIsHtb) {
                Htb::where('id', $oldHtbId)->decrement('filled');
            }
        }

        // Apply new counts
        if (! $newIsHtb && $newOdpId) {
            if ($customer->isDirty('odp_id') || $oldIsHtb) {
                Odp::where('id', $newOdpId)->increment('filled');
            }
        } elseif ($newIsHtb && $newHtbId) {
            if ($customer->isDirty('htb_id') || ! $oldIsHtb) {
                Htb::where('id', $newHtbId)->increment('filled');
            }
        }
    }
}
