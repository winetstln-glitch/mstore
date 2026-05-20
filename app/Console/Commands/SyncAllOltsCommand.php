<?php

namespace App\Console\Commands;

use App\Models\Olt;
use App\Services\Olt\OltService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAllOltsCommand extends Command
{
    protected $signature = 'olt:sync-all';
    protected $description = 'Sync all active OLTs and their ONUs';

    public function handle()
    {
        Log::info("[OLT SYNC] Starting sync all OLTs...");

        $olts = Olt::where('is_active', true)->get();
        $this->info("Found " . $olts->count() . " active OLTs");

        $service = new OltService();

        foreach ($olts as $olt) {
            $this->info("Processing OLT: {$olt->name} (ID: {$olt->id})");
            Log::info("[OLT SYNC] Processing OLT: {$olt->name} (ID: {$olt->id})");

            try {
                $driver = $service->getDriver($olt);
                $driver->connect($olt, 30);
                $onus = $driver->getOnus();
                $driver->disconnect();

                if (count($onus) > 0) {
                    $foundInterfaces = [];
                    foreach ($onus as $data) {
                        $olt->onus()->updateOrCreate(
                            ['interface' => $data['interface']],
                            array_merge($data, ['last_updated' => now()])
                        );
                        $foundInterfaces[] = $data['interface'];
                    }

                    if (!empty($foundInterfaces)) {
                        $deletedCount = $olt->onus()->whereNotIn('interface', $foundInterfaces)->delete();
                        if ($deletedCount > 0) {
                            $this->info("  Deleted {$deletedCount} old ONUs");
                            Log::info("[OLT SYNC] OLT {$olt->id} deleted {$deletedCount} old ONUs");
                        }
                    }

                    $olt->update(['last_synced_at' => now()]);
                    $this->info("  Success: Synced " . count($onus) . " ONUs");
                    Log::info("[OLT SYNC] OLT {$olt->id} success: synced " . count($onus) . " ONUs");
                } else {
                    $this->warn("  No ONUs found for this OLT");
                    Log::warning("[OLT SYNC] OLT {$olt->id} no ONUs found");
                }
            } catch (\Exception $e) {
                $this->error("  Error: " . $e->getMessage());
                Log::error("[OLT SYNC] OLT {$olt->id} error: " . $e->getMessage());
                Log::error("[OLT SYNC] OLT {$olt->id} stack trace: " . $e->getTraceAsString());
            }
        }

        $this->info("Done!");
        Log::info("[OLT SYNC] Sync all OLTs completed");
        return 0;
    }
}
