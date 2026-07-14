<?php

namespace App\Jobs\Noc;

use App\Actions\Noc\CaptureNocMetricsAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CaptureNocMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('monitoring');
    }

    public function handle(CaptureNocMetricsAction $action): void
    {
        $action->execute();
    }
}

