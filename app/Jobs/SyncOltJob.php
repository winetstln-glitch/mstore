<?php

namespace App\Jobs;

use App\Models\Olt;
use App\Services\Olt\OltService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncOltJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $olt;
    public $timeout = 300;
    public $tries = 1;

    public function __construct(Olt $olt)
    {
        $this->olt = $olt;
    }

    public function handle(OltService $oltService)
    {
        try {
            Log::info("[OLT SYNC JOB] Starting sync for OLT: {$this->olt->name} (ID: {$this->olt->id})");

            $driver = $oltService->getDriver($this->olt);
            $driver->connect($this->olt, 30);
            $onuDataList = $driver->getOnus();
            $driver->disconnect();

            $count = 0;
            $foundInterfaces = [];
            if (!empty($onuDataList)) {
                foreach ($onuDataList as $data) {
                    $this->olt->onus()->updateOrCreate(
                        ['interface' => $data['interface']],
                        array_merge($data, ['last_updated' => now()])
                    );
                    $foundInterfaces[] = $data['interface'];
                    $count++;
                }

                Log::info("[OLT SYNC JOB] SUCCESS: Synced {$count} ONUs for OLT: {$this->olt->name}");
            } else {
                Log::warning("[OLT SYNC JOB] No ONUs found for OLT: {$this->olt->name}");
            }

            if (!empty($foundInterfaces)) {
                $deletedCount = $this->olt->onus()->whereNotIn('interface', $foundInterfaces)->delete();
                Log::info("[OLT SYNC JOB] Deleted {$deletedCount} old ONUs that are no longer present");
            }

            $this->olt->update(['last_synced_at' => now()]);

        } catch (\Exception $e) {
            Log::error("[OLT SYNC JOB] FAILED for OLT: {$this->olt->name} - Error: " . $e->getMessage());
            throw $e;
        }
    }
}
