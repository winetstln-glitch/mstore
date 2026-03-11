<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class NetworkMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:network {--queue : Jalankan asinkron melalui queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alias lama untuk monitoring jaringan, diarahkan ke app:monitor-genie-devices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('Perintah monitor:network adalah alias lama. Gunakan app:monitor-genie-devices.');
        $options = [];
        if ((bool) $this->option('queue')) {
            $options['--queue'] = true;
        }

        return $this->call('app:monitor-genie-devices', $options);
    }
}
