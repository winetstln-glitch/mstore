<?php
// app/Services/OLT/Drivers/CDataDriver.php

namespace App\Services\OLT\Drivers;

use App\Services\OLT\Contracts\OLTDriverInterface;
use App\Services\SNMP\SNMPHelper;
use Illuminate\Support\Facades\Log;

class CDataDriver implements OLTDriverInterface
{
    protected SNMPHelper $snmp;
    protected string $ip;
    protected string $readCommunity;
    protected ?string $writeCommunity;
    protected ?\App\Models\Olt $oltInstance = null;

    const OID_SYS = [
        'model_name'   => '.1.3.6.1.4.1.17409.2.3.1.2.1.1.2.1',
        'uptime'       => '.1.3.6.1.4.1.17409.2.3.1.2.1.1.5.1',
        'vendor'       => '.1.3.6.1.4.1.17409.2.3.1.2.1.1.10.1',
        'serial'       => '.1.3.6.1.4.1.17409.2.3.1.3.1.1.12.1.0',
        'firmware'     => '.1.3.6.1.4.1.17409.2.3.1.3.1.1.15.1.0',
        'firmware_ver' => '.1.3.6.1.4.1.17409.2.3.1.3.1.1.9.1.0',
        'sys_uptime'   => '.1.3.6.1.4.1.17409.2.3.1.3.1.1.10.1.0',
    ];

    const OID_ONT = [
        'name'       => '.1.3.6.1.4.1.17409.2.8.4.1.1.2',
        'mac'        => '.1.3.6.1.4.1.17409.2.8.4.1.1.3',
        'status'     => '.1.3.6.1.4.1.17409.2.8.4.1.1.4',
        'vendor'     => '.1.3.6.1.4.1.17409.2.8.4.1.1.5',
        'model'      => '.1.3.6.1.4.1.17409.2.8.4.1.1.6',
        'rx_power'   => '.1.3.6.1.4.1.17409.2.3.3.6.1.2',
    ];

    const PORT_PREFIXES = [
        'gpon 0/0/1',
        'gpon 0/0/2',
        'gpon 0/0/3',
        'gpon 0/0/4',
        'gpon 0/0/5',
        'gpon 0/0/6',
        'gpon 0/0/7',
        'gpon 0/0/8',
    ];

    public function __construct(string $ip, string $readCommunity = 'public', ?string $writeCommunity = null)
    {
        $this->ip = $ip;
        $this->readCommunity = $readCommunity;
        $this->writeCommunity = $writeCommunity;
        $this->snmp = new SNMPHelper($ip, $readCommunity, $writeCommunity);
    }

    public function connect($olt, int $timeout = 10): void
    {
        $this->oltInstance = $olt;
        
        $this->ip = $olt->ip_address ?? $this->ip;
        $this->readCommunity = $olt->read_community ?? $this->readCommunity;
        $this->writeCommunity = $olt->write_community ?? $this->writeCommunity;
        
        $this->snmp = new SNMPHelper(
            $this->ip,
            $this->readCommunity,
            $this->writeCommunity,
            $timeout,
            0
        );
        
        Log::info("CDataDriver connected to {$this->ip}");
    }

    public function disconnect(): void
    {
        $this->oltInstance = null;
        Log::info("CDataDriver disconnected from {$this->ip}");
    }

    public function testConnection(): bool
    {
        try {
            $result = $this->snmp->get('.1.3.6.1.4.1.17409.2.3.1.2.1.1.2.1');
            return !empty($result);
        } catch (\Throwable $e) {
            Log::error("C-Data OLT connection test failed: " . $e->getMessage());
            return false;
        }
    }

    public function getDeviceInfo(): array
    {
        $info = [];
        foreach (self::OID_SYS as $key => $oid) {
            $value = $this->snmp->get($oid);
            
            if ($key === 'uptime' || $key === 'sys_uptime') {
                $value = $this->snmp->parseTimeticks($value);
            }
            
            $info[$key] = $value;
        }

        return $info;
    }

    public function getSystemResources(): array
    {
        return [
            'cpu_usage' => null,
            'memory_usage' => null,
            'temperature' => null,
        ];
    }

    public function getPorts(): array
    {
        $ports = [];
        foreach (self::PORT_PREFIXES as $i => $portName) {
            $portIndex = $i + 1;
            $index = 4718592 + ($i * 4096);
            $ports[$index] = [
                'name' => 'PON' . str_pad($portIndex, 2, '0', STR_PAD_LEFT),
                'index' => $index,
                'type' => 'pon',
                'rx_bytes' => 0,
                'tx_bytes' => 0,
                'admin_status' => 'up',
                'oper_status' => 'up',
            ];
        }

        $result = [];
        foreach ($ports as $port) {
            $result[$port['name']] = $port;
        }
        return $result;
    }

    public function getOnts(string $portName): array
    {
        $ports = $this->getPorts();
        $portMap = [];
        foreach ($ports as $name => $port) {
            $portIndex = (int)str_replace('PON', '', $name);
            $prefix = 'gpon 0/0/' . $portIndex;
            $portMap[$prefix] = [
                'port_name' => $name,
                'port_index' => $port['index'],
            ];
        }

        $onts = [];
        
        $allRaw = $this->snmp->walk('.1.3.6.1.4.1.17409.2.8.4.1.1');
        
        $ontData = [];
        foreach ($allRaw as $line) {
            $parts = explode(' = ', $line, 2);
            if (count($parts) !== 2) continue;
            
            $oidPart = $parts[0];
            $valuePart = $parts[1];
            
            $oidComponents = explode('.', ltrim($oidPart, '.'));
            $numComponents = count($oidComponents);
            
            if ($numComponents < 14) continue;
            
            $fieldIdx = (int)$oidComponents[12];
            $ontIndex = $oidComponents[13];
            $value = $this->snmp->stripTypePrefix(trim($valuePart));
            
            if (!isset($ontData[$ontIndex])) {
                $ontData[$ontIndex] = [];
            }
            
            switch ($fieldIdx) {
                case 2:
                    $ontData[$ontIndex]['name'] = trim($value, '"');
                    break;
                case 3:
                    $ontData[$ontIndex]['mac'] = $this->parseHexMac($value);
                    break;
                case 4:
                    $ontData[$ontIndex]['status'] = $value == 1 ? 'online' : 'offline';
                    break;
                case 5:
                    $ontData[$ontIndex]['vendor'] = trim($value, '"');
                    break;
                case 6:
                    $ontData[$ontIndex]['model'] = trim($value, '"');
                    break;
            }
        }
        
        $rxPowerRaw = $this->snmp->walk(self::OID_ONT['rx_power']);
        $rxPowerMap = [];
        foreach ($rxPowerRaw as $line) {
            $parts = explode(' = ', $line, 2);
            if (count($parts) !== 2) continue;
            $oidPart = $parts[0];
            $valuePart = $parts[1];
            $oidComponents = explode('.', ltrim($oidPart, '.'));
            $ontIndex = end($oidComponents);
            $val = (int)$this->snmp->stripTypePrefix($valuePart);
            if ($val === 0) {
                $rxPowerMap[$ontIndex] = null;
            } else {
                $rxPowerMap[$ontIndex] = $val / 100;
            }
        }

        foreach ($ontData as $ontIndex => $data) {
            if (!isset($data['name'])) continue;
            
            $ontName = $data['name'];
            
            $matchedPort = null;
            foreach ($portMap as $prefix => $portInfo) {
                if (str_starts_with($ontName, $prefix)) {
                    $matchedPort = $portInfo;
                    break;
                }
            }

            if (!$matchedPort) continue;
            if ($matchedPort['port_name'] !== $portName) continue;

            $rxPower = $rxPowerMap[$ontIndex] ?? null;
            
            if ($rxPower !== null && $rxPower !== 0) {
                $status = 'online';
            } else {
                $status = 'offline';
            }
            
            $ontResult = [
                'port_index' => $matchedPort['port_index'],
                'ont_index' => (int)$ontIndex,
                'ont_id' => $ontName,
                'pon_port' => $matchedPort['port_name'],
                'status' => $status,
                'mac_address' => $data['mac'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'model' => $data['model'] ?? null,
                'rx_power' => $rxPower,
            ];

            $onts[] = $ontResult;
        }

        return $onts;
    }

    public function getOnus(): array
    {
        $onus = [];
        
        $ports = $this->getPorts();
        $portMap = [];
        foreach ($ports as $name => $port) {
            $portIndex = (int)str_replace('PON', '', $name);
            $prefix = 'gpon 0/0/' . $portIndex;
            $portMap[$prefix] = [
                'port_name' => $name,
                'port_index' => $port['index'],
            ];
        }
        
        $allRaw = $this->snmp->walk('.1.3.6.1.4.1.17409.2.8.4.1.1');
        $rxPowerRaw = $this->snmp->walk(self::OID_ONT['rx_power']);
        
        $ontData = [];
        foreach ($allRaw as $line) {
            $parts = explode(' = ', $line, 2);
            if (count($parts) !== 2) continue;
            
            $oidPart = $parts[0];
            $valuePart = $parts[1];
            
            $oidComponents = explode('.', ltrim($oidPart, '.'));
            $numComponents = count($oidComponents);
            
            if ($numComponents < 14) continue;
            
            $fieldIdx = (int)$oidComponents[12];
            $ontIndex = $oidComponents[13];
            $value = $this->snmp->stripTypePrefix(trim($valuePart));
            
            if (!isset($ontData[$ontIndex])) {
                $ontData[$ontIndex] = [];
            }
            
            switch ($fieldIdx) {
                case 2:
                    $ontData[$ontIndex]['name'] = trim($value, '"');
                    break;
                case 3:
                    $ontData[$ontIndex]['mac'] = $this->parseHexMac($value);
                    break;
                case 4:
                    $ontData[$ontIndex]['status_val'] = $value;
                    break;
                case 5:
                    $ontData[$ontIndex]['vendor'] = trim($value, '"');
                    break;
                case 6:
                    $ontData[$ontIndex]['model'] = trim($value, '"');
                    break;
            }
        }
        
        $rxPowerMap = [];
        foreach ($rxPowerRaw as $line) {
            $parts = explode(' = ', $line, 2);
            if (count($parts) !== 2) continue;
            $oidPart = $parts[0];
            $valuePart = $parts[1];
            $oidComponents = explode('.', ltrim($oidPart, '.'));
            $ontIndex = end($oidComponents);
            $val = (int)$this->snmp->stripTypePrefix($valuePart);
            if ($val === 0) {
                $rxPowerMap[$ontIndex] = null;
            } else {
                $rxPowerMap[$ontIndex] = $val / 100;
            }
        }
        
        foreach ($ontData as $ontIndex => $data) {
            if (!isset($data['name'])) continue;
            
            $ontName = $data['name'];
            $matchedPort = null;
            foreach ($portMap as $prefix => $portInfo) {
                if (str_starts_with($ontName, $prefix)) {
                    $matchedPort = $portInfo;
                    break;
                }
            }
            if (!$matchedPort) continue;
            
            $rxPower = $rxPowerMap[$ontIndex] ?? null;
            
            if ($rxPower !== null && $rxPower !== 0) {
                $status = 'online';
            } else {
                $status = 'offline';
            }
            
            $onus[] = [
                'interface' => $matchedPort['port_name'] . '/' . $ontName,
                'ont_id' => $ontName,
                'name' => $ontName,
                'vendor' => $data['vendor'] ?? null,
                'model' => $data['model'] ?? null,
                'serial_number' => null,
                'mac_address' => $data['mac'] ?? null,
                'rx_power' => $rxPower,
                'tx_power' => null,
                'voltage' => null,
                'temperature' => null,
                'status' => $status,
                'pon_port' => $matchedPort['port_name'],
            ];
        }
        
        return $onus;
    }

    public function getOntDetail(string $ontId): array
    {
        return ['ont_id' => $ontId];
    }

    public function getOntOpticalInfo(string $ontId): array
    {
        return [];
    }

    public function getOntTraffic(string $ontId): array
    {
        return [];
    }

    public function rebootOnt(string $ontId): bool
    {
        return false;
    }

    public function getAlarms(): array
    {
        return [];
    }

    protected function parseHexMac(?string $raw): string
    {
        if (empty($raw)) return '';
        $hex = preg_replace('/[^0-9A-Fa-f]/', '', $raw);
        if (strlen($hex) === 12) {
            return implode(':', str_split(strtoupper($hex), 2));
        }
        return $raw;
    }
}
