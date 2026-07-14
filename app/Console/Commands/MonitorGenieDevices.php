<?php

namespace App\Console\Commands;

use App\Jobs\NetworkMonitorJob;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MonitorGenieDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitor-genie-devices {--queue : Jalankan asinkron melalui queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor perangkat GenieACS pelanggan dan buat tiket gangguan otomatis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai monitor perangkat GenieACS...');

        if ((bool) $this->option('queue')) {
            if ($this->shouldRunSyncFallback()) {
                $this->warn('Fallback aktif: monitor dijalankan sinkron karena antrean monitoring terdeteksi tidak sehat.');
                NetworkMonitorJob::dispatchSync();
                $this->info('Monitor perangkat GenieACS selesai (mode fallback sinkron).');

                return self::SUCCESS;
            }

            NetworkMonitorJob::dispatch()->onQueue('monitoring');
            $this->info('Job monitor dikirim ke queue monitoring.');

            return self::SUCCESS;
        }

        NetworkMonitorJob::dispatchSync();
        $this->info('Monitor perangkat GenieACS selesai.');

        return self::SUCCESS;
    }

    protected function shouldRunSyncFallback(): bool
    {
        $summaryRaw = Setting::getValue('network_monitor_summary');
        if (! is_string($summaryRaw) || trim($summaryRaw) === '') {
            return true;
        }

        $summary = json_decode($summaryRaw, true);
        if (! is_array($summary)) {
            return true;
        }

        $ranAt = $summary['ran_at'] ?? null;
        if (! is_string($ranAt) || trim($ranAt) === '') {
            return true;
        }

        try {
            return Carbon::parse($ranAt)->lt(now()->subMinutes(20));
        } catch (\Throwable $e) {
            return true;
        }
    }
}
