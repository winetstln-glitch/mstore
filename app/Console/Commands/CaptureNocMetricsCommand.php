<?php

namespace App\Console\Commands;

use App\Jobs\Noc\CaptureNocMetricsJob;
use Illuminate\Console\Command;

class CaptureNocMetricsCommand extends Command
{
    protected $signature = 'noc:capture-metrics {--queue : Jalankan melalui queue monitoring}';

    protected $description = 'Capture snapshot metrik NOC untuk Dashboard NOC';

    public function handle(): int
    {
        if ((bool) $this->option('queue')) {
            CaptureNocMetricsJob::dispatch();
            $this->info('CaptureNocMetricsJob dikirim ke queue monitoring.');

            return self::SUCCESS;
        }

        CaptureNocMetricsJob::dispatchSync();
        $this->info('Snapshot metrik NOC berhasil dibuat (sinkron).');

        return self::SUCCESS;
    }
}

