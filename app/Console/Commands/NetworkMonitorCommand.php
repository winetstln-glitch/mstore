<?php

namespace App\Console\Commands;

use App\Jobs\NetworkMonitorJob;
use Illuminate\Console\Command;

class NetworkMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:network';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check network status (ONU/PPPoE) and auto-create tickets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting network monitor...');
        NetworkMonitorJob::dispatchSync();
        $this->info('Network monitor completed.');
    }
}
