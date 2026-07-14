<?php
// app/Services/OLT/OLTPollService.php

/**
 * @deprecated Use Network module services instead
 */

namespace App\Services\Olt;

use App\Models\OLT;
use App\Models\OLTPort;
use App\Models\ONT;
use App\Models\PollingLog;
use App\Models\Alarm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OLTPollService
{
    protected OLTFactory $factory;
    
    public function __construct(OLTFactory $factory)
    {
        $this->factory = $factory;
    }

    public function poll(int $oltId): array
    {
        set_time_limit(300);
        
        $olt = OLT::findOrFail($oltId);
        $startTime = microtime(true);
        
        $result = [
            'status' => 'failed',
            'duration_ms' => 0,
            'onts_found' => 0,
            'error' => null,
        ];
        
        try {
            $driver = $this->factory->create($olt);
            Log::info("Polling OLT {$olt->name}: Step 1 - Creating driver done");
            
            if (!$driver->testConnection()) {
                throw new \RuntimeException("SNMP connection failed");
            }
            Log::info("Polling OLT {$olt->name}: Step 2 - Test connection done");
            
            // Skip device info and system resources for faster polling
            Log::info("Polling OLT {$olt->name}: Skipping device info and resources for speed");
            
            // 1. Ports
            $portsStart = microtime(true);
            $ports = $driver->getPorts();
            Log::info("Polling OLT {$olt->name}: Step 3 - Get ports done in " . round((microtime(true) - $portsStart) * 1000) . "ms, found " . count($ports) . " ports");
            $totalOnts = 0;
            
            // 2. Get all ONUs at once (optimized)
            $onusStart = microtime(true);
            $allOnus = method_exists($driver, 'getOnus') ? $driver->getOnus() : [];
            Log::info("Polling OLT {$olt->name}: Step 4 - Get all ONUs done in " . round((microtime(true) - $onusStart) * 1000) . "ms, found " . count($allOnus) . " ONUs");
            
            // Track which ONUs are present in OLT
            $foundOntIds = [];
            
            foreach ($ports as $portData) {
                $port = OLTPort::updateOrCreate(
                    ['olt_id' => $olt->id, 'name' => $portData['name']],
                    [
                        'type' => $portData['type'],
                        'rx_bytes' => $portData['rx_bytes'],
                        'tx_bytes' => $portData['tx_bytes'],
                        'oper_status' => $portData['oper_status'],
                    ]
                );
                
                // Filter ONUs for this port
                $ontsForPort = [];
                foreach ($allOnus as $ontData) {
                    if (isset($ontData['pon_port']) && $ontData['pon_port'] === $portData['name']) {
                        $ontsForPort[] = $ontData;
                    }
                }
                
                // If no ONUs from getOnus(), try per port getOnts()
                if (empty($ontsForPort)) {
                    try {
                        $ontsForPort = $driver->getOnts($portData['name']);
                    } catch (\Throwable $e) {
                        Log::warning("Polling OLT {$olt->name}: Failed to get ONUs for port {$portData['name']}: " . $e->getMessage());
                    }
                }
                
                foreach ($ontsForPort as $ontData) {
                    $status = $ontData['status'] ?? 'offline';
                    $status = $status == 1 || $status === 'online' ? 'online' : 'offline';
                    
                    $ont = ONT::updateOrCreate(
                        ['olt_id' => $olt->id, 'ont_id' => $ontData['ont_id']],
                        [
                            'olt_port_id' => $port->id,
                            'interface' => $portData['name'] . ':' . $ontData['ont_id'],
                            'name' => $ontData['ont_id'],
                            'vendor' => $ontData['vendor'] ?? null,
                            'model' => $ontData['model'] ?? null,
                            'firmware_version' => $ontData['firmware'] ?? null,
                            'mac_address' => $ontData['mac_address'] ?? $ontData['oui'] ?? null,
                            'mac' => $ontData['mac_address'] ?? $ontData['oui'] ?? null,
                            'serial_number' => $ontData['serial_number'] ?? null,
                            'rx_power' => $ontData['rx_power'] ?? null,
                            'tx_power' => $ontData['tx_power'] ?? null,
                            'voltage' => $ontData['voltage'] ?? null,
                            'temperature' => $ontData['temperature'] ?? null,
                            'status' => $status,
                            'oper_status' => $status,
                            'onu_index' => $ontData['ont_index'] ?? $ontData['ont_id'],
                            'last_polled_at' => now(),
                            'last_active_at' => now(),
                            'last_updated' => now(),
                        ]
                    );
                    
                    $foundOntIds[] = $ont->id;
                    $totalOnts++;
                }
            }
            
            // Mark ONUs not found in OLT as offline or delete them
            if (!empty($foundOntIds)) {
                ONT::where('olt_id', $olt->id)
                    ->whereNotIn('id', $foundOntIds)
                    ->update([
                        'status' => 'offline',
                        'oper_status' => 'offline',
                        'last_polled_at' => now(),
                    ]);
            }
            
            // Update OLT status
            $olt->update([
                'status' => 'online',
                'last_polled_at' => now(),
                'last_online_at' => now(),
            ]);
            
            $result['status'] = 'success';
            $result['onts_found'] = $totalOnts;
            
        } catch (\Throwable $e) {
            $result['status'] = 'failed';
            $result['error'] = $e->getMessage();
            
            Log::error("OLT poll failed for {$olt->name}: " . $e->getMessage());
            
            $olt->update(['status' => 'offline']);
            
            if ($olt->last_online_at && $olt->last_online_at->diffInMinutes(now()) > 5) {
                Alarm::create([
                    'olt_id' => $olt->id,
                    'severity' => 'critical',
                    'type' => 'olt_offline',
                    'message' => "OLT {$olt->name} ({$olt->ip_address}) unreachable",
                    'occurred_at' => now(),
                ]);
            }
        }
        
        $result['duration_ms'] = (int)((microtime(true) - $startTime) * 1000);
        
        PollingLog::create([
            'olt_id' => $olt->id,
            'status' => $result['status'],
            'duration_ms' => $result['duration_ms'],
            'onts_found' => $result['onts_found'],
            'error_message' => $result['error'],
            'polled_at' => now(),
        ]);
        
        return $result;
    }
}
