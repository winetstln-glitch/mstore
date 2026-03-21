<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Ticket;
use App\Services\GenieACSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NetworkMonitorJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'monitoring';

    public int $tries = 3;

    public int $timeout = 300;

    public int $uniqueFor = 600;

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
        Customer::query()
            ->select(['id', 'name', 'onu_serial'])
            ->where('status', 'active')
            ->whereNotNull('onu_serial')
            ->orderBy('id')
            ->chunkById(200, function ($customers) use ($genieService): void {
                foreach ($customers as $customer) {
                    $this->checkCustomer($customer, $genieService);
                }
            });
    }

    protected function checkCustomer(Customer $customer, GenieACSService $genieService): void
    {
        try {
            $isDown = false;
            $reason = '';

            if ($customer->onu_serial) {
                $onuStatus = $genieService->getDeviceStatus($customer->onu_serial);
                if (! ($onuStatus['online'] ?? false)) {
                    $isDown = true;
                    $reason = 'ONU Offline (Last seen: '.($onuStatus['last_inform'] ?? 'Never').')';
                }
            }

            if ($isDown) {
                $this->createTicketIfNeeded($customer, $reason);
            }
        } catch (Throwable $e) {
            Log::warning('Network monitor customer check failed', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function createTicketIfNeeded(Customer $customer, string $reason): void
    {
        $existingTicket = Ticket::where('customer_id', $customer->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->where('subject', 'like', 'Auto-Alert: %')
            ->exists();

        if (! $existingTicket) {
            Ticket::create([
                'customer_id' => $customer->id,
                'subject' => "Auto-Alert: Service Down - $reason",
                'description' => "System detected service interruption.\nReason: $reason\nTimestamp: ".now(),
                'status' => 'open',
                'priority' => 'high',
                // Assign to default technician or leave unassigned
            ]);

            Log::info("Auto-ticket created for customer {$customer->name} ($reason)");
        }
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Network monitor job failed', [
            'message' => $exception->getMessage(),
        ]);
    }
}
