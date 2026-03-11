<?php

use App\Models\VpnServer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule GenieACS Network Monitor
Schedule::command('app:monitor-genie-devices', ['--queue' => true])->everyTenMinutes();

Artisan::command('vpn:monitor', function () {
    $count = VpnServer::count();
    $bar = $this->output->createProgressBar($count);
    $bar->start();
    $updated = 0;
    VpnServer::each(function (VpnServer $server) use (&$updated, $bar) {
        $start = microtime(true);
        $ok = false;
        try {
            $fp = @fsockopen($server->ip_public, $server->port, $errno, $errstr, 2.0);
            if ($fp) {
                fclose($fp);
                $ok = true;
            }
        } catch (\Throwable $e) {
        }
        $lat = (int) round((microtime(true) - $start) * 1000);
        $server->last_latency_ms = $ok ? $lat : null;
        $server->save();
        $updated++;
        $bar->advance();
    });
    $bar->finish();
    $this->newLine();
    $this->info("Updated latency for {$updated} servers.");
})->purpose('Measure latency to each VPN server and store it');
