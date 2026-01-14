<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Ticket;
use App\Services\GenieACSService;
use App\Services\MikrotikService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NetworkMonitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(GenieACSService $genieService): void
    {
        // Get active customers with technical details
        $customers = Customer::where('status', 'active')
            ->whereNotNull('onu_serial')
            ->get();

        foreach ($customers as $customer) {
            $isDown = false;
            $reason = '';

            // 1. Check ONU Status via GenieACS (if serial exists)
            if ($customer->onu_serial) {
                $onuStatus = $genieService->getDeviceStatus($customer->onu_serial);
                if (!$onuStatus['online']) {
                    $isDown = true;
                    $reason = "ONU Offline (Last seen: " . ($onuStatus['last_inform'] ?? 'Never') . ")";
                }
            }

            // 2. Auto-Create Ticket if Down
            if ($isDown) {
                $this->createTicketIfNeeded($customer, $reason);
            }
        }
    }

    protected function createTicketIfNeeded(Customer $customer, $reason)
    {
        // Check if there is already an open ticket for this issue
        $existingTicket = Ticket::where('customer_id', $customer->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->where('subject', 'like', 'Auto-Alert: %')
            ->exists();

        if (!$existingTicket) {
            Ticket::create([
                'customer_id' => $customer->id,
                'subject' => "Auto-Alert: Service Down - $reason",
                'description' => "System detected service interruption.\nReason: $reason\nTimestamp: " . now(),
                'status' => 'open',
                'priority' => 'high',
                // Assign to default technician or leave unassigned
            ]);
            
            Log::info("Auto-ticket created for customer {$customer->name} ($reason)");
        }
    }
}
