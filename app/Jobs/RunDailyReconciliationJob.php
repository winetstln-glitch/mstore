<?php

namespace App\Jobs;

use App\Models\BusinessUnit;
use App\Services\LedgerReconciliationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunDailyReconciliationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?Carbon $date = null)
    {
        $this->date = $date ?? Carbon::yesterday();
    }

    public function handle(LedgerReconciliationService $service): void
    {
        $businessUnits = BusinessUnit::all();

        foreach ($businessUnits as $bu) {
            $service->reconcileDay($this->date, $bu->id);
        }

        $service->reconcileDay($this->date, null);
    }
}