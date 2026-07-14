<?php
// app/Console/Commands/PollOLT.php

namespace App\Console\Commands;

use App\Models\OLT;
use Modules\Network\Services\MonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PollOLT extends Command
{
    protected $signature = 'olt:poll 
                            {--olt= : Specific OLT ID to poll}
                            {--all : Poll all active OLTs}
                            {--daemon : Run as continuous daemon}';
    
    protected $description = 'Poll OLT devices via SNMP';

    public function __construct(protected MonitoringService $monitoringService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('daemon')) {
            return $this->runDaemon();
        }
        
        $olts = $this->getOltList();
        
        foreach ($olts as $olt) {
            $this->info("Polling {$olt->name} ({$olt->vendor}) at {$olt->ip_address}...");
            
            $result = $this->monitoringService->pollOlt($olt->id);
            
            $this->line("  Status: {$result['status']}");
            $this->line("  Duration: {$result['duration_ms']}ms");
            $this->line("  ONTs found: {$result['onts_found']}");
            
            if ($result['error']) {
                $this->error("  Error: {$result['error']}");
            }
            
            $this->newLine();
        }
        
        return Command::SUCCESS;
    }

    protected function runDaemon(): int
    {
        $this->info('OLT Polling Daemon started');
        $this->info('Press Ctrl+C to stop');
        $this->newLine();
        
        while (true) {
            $olts = OLT::where('is_active', true)->get();
            $startTime = now();
            
            foreach ($olts as $olt) {
                $this->info("[{$startTime->format('H:i:s')}] Polling {$olt->name}...");
                
                try {
                    $result = $this->monitoringService->pollOlt($olt->id);
                    $this->line("  -> {$result['status']} ({$result['duration_ms']}ms, {$result['onts_found']} ONTs)");
                } catch (\Throwable $e) {
                    $this->error("  -> Failed: {$e->getMessage()}");
                }
            }
            
            $elapsed = now()->diffInSeconds($startTime);
            $sleepTime = max(30, 300 - $elapsed); // Sleep minimal 30 detik, max 5 menit dari start
            
            $this->line("  Sleeping for {$sleepTime}s...");
            sleep($sleepTime);
        }
    }

    protected function getOltList()
    {
        if ($oltId = $this->option('olt')) {
            return OLT::where('id', $oltId)->where('is_active', true)->get();
        }
        
        if ($this->option('all')) {
            return OLT::where('is_active', true)->get();
        }
        
        // Interactive selection
        $olts = OLT::where('is_active', true)->pluck('name', 'id')->toArray();
        
        if (empty($olts)) {
            $this->error('No active OLTs found');
            return collect();
        }
        
        $selected = $this->choice('Select OLT to poll', array_keys($olts));
        $oltId = array_search($selected, $olts);
        
        return OLT::where('id', $oltId)->get();
    }
}