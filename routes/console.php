<?php

use App\Console\Commands\MarkAbsentAsAlpha;
use App\Models\VpnServer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule GenieACS Network Monitor
Schedule::command('app:monitor-genie-devices')->everyFiveMinutes()->withoutOverlapping(10);

Schedule::command('noc:capture-metrics --queue')->everyMinute()->withoutOverlapping(2);

Schedule::command('sla:evaluate --queue')->everyFifteenMinutes()->withoutOverlapping(10);

Schedule::call(function () {
    Cache::put('mstore.scheduler.heartbeat_at', now()->toDateTimeString(), now()->addMinutes(10));
})->name('mstore:scheduler-heartbeat')->everyMinute()->withoutOverlapping(1);

// Schedule Attendance: Mark Absent as Alpha (run at 13:05 and 17:05 daily)
Schedule::command('attendance:mark-alpha')
    ->dailyAt('13:05')
    ->withoutOverlapping(10);

Schedule::command('attendance:mark-alpha')
    ->dailyAt('17:05')
    ->withoutOverlapping(10);

// Register command manually
Artisan::registerCommand(new MarkAbsentAsAlpha());

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
