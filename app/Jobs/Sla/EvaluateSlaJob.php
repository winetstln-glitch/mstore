<?php

namespace App\Jobs\Sla;

use App\Services\SlaMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateSlaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('monitoring');
    }

    public function handle(SlaMonitoringService $service): void
    {
        $service->evaluateOpenTickets();
        DispatchSlaEscalationNotificationsJob::dispatch();
    }
}

