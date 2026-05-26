<?php
// app/Services/OLT/Drivers/HSGQDriver.php

namespace App\Services\OLT\Drivers;

use App\Services\OLT\Contracts\OLTDriverInterface;
use App\Services\SNMP\SNMPHelper;
use Illuminate\Support\Facades\Log;

class HSGQDriver implements OLTDriverInterface
{
    protected SNMPHelper $snmp;
    protected string $ip;
    protected string $readCommunity;
    protected ?string $writeCommunity;
    protected ?\App\Models\OLT $oltInstance = null;

    const OID_SYS = [
        'mac'          => '.1.3.6.1.4.1.50224.3.1.1.1.0',
        'date'         => '.1.3.6.1.4.1.50224.3.1.1.2.0',
        'uptime'       => '.1.3.6.1.4.1.50224.3.1.1.3.0',
        'sys_time'     => '.1.3.6.1.4.1.50224.3.1.1.4.0',
        'firmware'     => '.1.3.6.1.4.1.50224.3.1.1.5.0',
        'hardware'     => '.1.3.6.1.4.1.50224.3.1.1.6.0',
        'software_ver' => '.1.3.6.1.4.1.50224.3.1.1.7.0',
        'cpu_usage'    => '.1.3.6.1.4.1.50224.3.1.1.8.0',
        'mem_usage'    => '.1.3.6.1.4.1.50224.3.1.1.9.0',
        'temp'         => '.1.3.6.1.4.1.50224.3.1.1.10.0',
        'ont_count'    => '.1.3.6.1.4.1.50224.3.1.1.11.0',
        'alarm_count'  => '.1.3.6.1.4.1.50224.3.1.1.12.0',
        'read_comm'    => '.1.3.6.1.4.1.50224.3.1.1.15.0',
        'write_comm'   => '.1.3.6.1.4.1.50224.3.1.1.16.0',
        'model_name'   => '.1.3.6.1.4.1.50224.3.1.1.19.0',
        'serial'       => '.1.3.6.1.4.1.50224.3.1.1.20.0',
        'fan_status'   => '.1.3.6.1.4.1.50224.3.1.1.21.0',
        'power_type'   => '.1.3.6.1.4.1.50224.3.1.1.24.0',
    ];

    const PORT_MAP = [
        16777472 => 'PON01',
        16777728 => 'PON02',
        16777984 => 'GE01',
        16778240 => 'GE02',
        16778496 => 'GE03',
        16778752 => 'GE04',
        16779008 => 'XGE01',
    ];

    public function __construct(string $ip, string $readCommunity = 'public', ?string $writeCommunity = null)
    {
        $this->ip = $ip;
        $this->readCommunity = $readCommunity;
        $this->writeCommunity = $writeCommunity;
        $this->snmp = new SNMPHelper($ip, $readCommunity, $writeCommunity);
    }

    public function connect($olt, int $timeout = 30): void
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
            2
        );
        
        Log::info("HSGQDriver connected to {$this->ip}");
    }

    public function disconnect(): void
    {
        $this->oltInstance = null;
        Log::info("HSGQDriver disconnected from {$this->ip}");
    }

    public function getDeviceInfo(): array
    {
        $info = [];
        foreach (self::OID_SYS as $key => $oid) {
            $value = $this->snmp->get($oid);
            
            if ($key === 'uptime') {
                $value = $this->snmp->parseTimeticks($value);
            }
            
            $info[$key] = $value;
        }
        
        $macRaw = $this->snmp->get('.1.3.6.1.4.1.50224.3.1.1.1.0');
        if ($macRaw) {
            $info['mac'] = $this->parseHexMac($macRaw);
        }
        
        $firmwareRaw = $this->snmp->get('.1.3.6.1.4.1.50224.3.1.1.5.0');
        if ($firmwareRaw) {
            $info['firmware'] = $this->parseHexString($firmwareRaw);
        }
        
        $hardwareRaw = $this->snmp->get('.1.3.6.1.4.1.50224.3.1.1.6.0');
        if ($hardwareRaw) {
            $info['hardware'] = $this->parseHexString($hardwareRaw);
        }
        
        $serialRaw = $this->snmp->get('.1.3.6.1.4.1.50224.3.1.1.20.0');
        if ($serialRaw) {
            $info['serial'] = $this->snmp->stripTypePrefix($serialRaw);
        }
        
        $readCommRaw = $this->snmp->get('.1.3.6.1.4.1.50224.3.1.1.15.0');
        if ($readCommRaw) {
            $info['read_comm'] = $this->parseHexString($readCommRaw);
        }
        
        $writeCommRaw = $this->snmp->get('.1.3.6.1.4.1.50224.3.1.1.16.0');
        if ($writeCommRaw) {
            $info['write_comm'] = $this->parseHexString($writeCommRaw);
        }
        
        return $info;
    }
    
    protected function parseHexMac(?string $raw): string
    {
        if (empty($raw)) return '';
        $raw = $this->snmp->stripTypePrefix($raw);
        $hex = preg_replace('/[^0-9A-Fa-f]/', '', $raw);
        if (strlen($hex) === 12) {
            return implode(':', str_split(strtoupper($hex), 2));
        }
        return $raw;
    }
    
    protected function parseHexString(?string $raw): string
    {
        if (empty($raw)) return '';
        $raw = $this->snmp->stripTypePrefix($raw);
        $hex = preg_replace('/[^0-9A-Fa-f]/', '', $raw);
        if (strlen($hex) < 2) return $raw;
        
        $bytes = array_map('hexdec', str_split($hex, 2));
        $result = '';
        foreach ($bytes as $byte) {
            if ($byte >= 32 && $byte <= 126) {
                $result .= chr($byte);
            }
        }
        return trim($result) ?: $raw;
    }

    public function getPorts(): array
    {
        $ports = [];
        foreach (self::PORT_MAP as $index => $name) {
            $ports[$index] = [
                'name' => $name,
                'index' => $index,
                'type' => str_starts_with($name, 'PON') ? 'pon' : (str_starts_with($name, 'XGE') ? 'xge' : 'ge'),
                'rx_bytes' => 0,
                'tx_bytes' => 0,
                'admin_status' => 'up',
                'oper_status' => 'up',
            ];
        }

        $rxRaw = $this->snmp->walk('.1.3.6.1.4.1.50224.3.12.1.1.4');
        $txRaw = $this->snmp->walk('.1.3.6.1.4.1.50224.3.12.1.1.5');

        foreach ($rxRaw as $line) {
            $parts = explode(' = ', $line, 2);
            if (count($parts) !== 2) continue;
            $oidPart = $parts[0];
            $valuePart = $parts[1];
            $oidComponents = explode('.', ltrim($oidPart, '.'));
            if (count($oidComponents) < 16) continue;
            $index = (int)$oidComponents[13];
            $value = (int)$this->snmp->stripTypePrefix($valuePart);
            if (isset($ports[$index])) {
                $ports[$index]['rx_bytes'] = $value;
            }
        }

        foreach ($txRaw as $line) {
            $parts = explode(' = ', $line, 2);
            if (count($parts) !== 2) continue;
            $oidPart = $parts[0];
            $valuePart = $parts[1];
            $oidComponents = explode('.', ltrim($oidPart, '.'));
            if (count($oidComponents) < 16) continue;
            $index = (int)$oidComponents[13];
            $value = (int)$this->snmp->stripTypePrefix($valuePart);
            if (isset($ports[$index])) {
                $ports[$index]['tx_bytes'] = $value;
            }
        }

        $result = [];
        foreach ($ports as $port) {
            $result[$port['name']] = $port;
        }
        return $result;
    }

    public function getOnts(string $portName): array
    {
        $portIndex = array_search($portName, self::PORT_MAP);
        if ($portIndex === false) return [];

        $portIndexes = array_keys(self::PORT_MAP);
        $currentPortPos = array_search($portIndex, $portIndexes);
        $nextPortIndex = $portIndexes[$currentPortPos + 1] ?? PHP_INT_MAX;

        $onts = [];
        $raw = $this->snmp->walk(".1.3.6.1.4.1.50224.3.12.2.1.2");
        $statusRaw = $this->snmp->walk(".1.3.6.1.4.1.50224.3.12.2.1.3");
        
        $statusMap = [];
        foreach ($statusRaw as $line) {
            $parts = explode(' = ', $line, 2);
            if (count($parts) !== 2) continue;
            
            $oidPart = $parts[0];
            $valuePart = $parts[1];
            
            $oidComponents = explode('.', ltrim($oidPart, '.'));
            if (count($oidComponents) < 1) continue;
            
            $ontIndex = (int)end($oidComponents);
            $statusMap[$ontIndex] = $this->snmp->stripTypePrefix(trim($valuePart));
        }
        
        $detailsRaw = $this->snmp->walk('.1.3.6.1.4.1.50224.3.12.2');
        $details = [];
        foreach ($detailsRaw as $line) {
            $parts = explode(' = ', $line, 2);
            if (count($parts) !== 2) continue;
            
            $oidPart = $parts[0];
            $valuePart = $parts[1];
            
            $oidComponents = explode('.', ltrim($oidPart, '.'));
            if (count($oidComponents) < 13) continue;
            
            $fieldIdx = (int)$oidComponents[11];
            $ontIndex = (int)$oidComponents[12];
            $value = $this->snmp->stripTypePrefix(trim($valuePart));
            
            if (!isset($details[$ontIndex])) {
                $details[$ontIndex] = [];
            }
            
            switch ($fieldIdx) {
                case 8:
                    $details[$ontIndex]['vendor'] = $value;
                    break;
                case 9:
                    $details[$ontIndex]['model'] = $value;
                    break;
                case 10:
                    $details[$ontIndex]['oui'] = $value;
                    break;
                case 11:
                    $details[$ontIndex]['firmware'] = $value;
                    break;
                case 15:
                    $details[$ontIndex]['serial_number'] = $value;
                    break;
            }
        }

        $opticalRaw = $this->snmp->walk('.1.3.6.1.4.1.50224.3.12.3.1');
        $optical = [];
        foreach ($opticalRaw as $line) {
            $parts = explode(' = ', $line, 2);
            if (count($parts) !== 2) continue;
            
            $oidPart = $parts[0];
            $valuePart = $parts[1];
            
            $oidComponents = explode('.', ltrim($oidPart, '.'));
            // For debugging:
            if (count($oidComponents) > 10) {
                $fieldIdx = (int)($oidComponents[11] ?? -1);
                $ontIndex = (int)($oidComponents[12] ?? -1);
                $channelIndex = (int)($oidComponents[13] ?? -1);
                $specialIndex = (int)($oidComponents[14] ?? -1);
            } else {
                continue;
            }
            
            if ($channelIndex !== 0 || $specialIndex !== 0) {
                continue;
            }
            
            $value = $this->snmp->stripTypePrefix(trim($valuePart));
            $valueFloat = (float)$value;
            
            if (!isset($optical[$ontIndex])) {
                $optical[$ontIndex] = [];
            }
            
            switch ($fieldIdx) {
                case 4:
                    $optical[$ontIndex]['rx_power'] = $valueFloat / 100;
                    break;
                case 5:
                    $optical[$ontIndex]['tx_power'] = $valueFloat / 100;
                    break;
                case 6:
                    $optical[$ontIndex]['voltage'] = $valueFloat / 100;
                    break;
                case 7:
                    $optical[$ontIndex]['temperature'] = $valueFloat / 100;
                    break;
            }
        }
        
        foreach ($raw as $line) {
            $parts = explode(' = ', $line, 2);
            if (count($parts) !== 2) continue;
            
            $oidPart = $parts[0];
            $valuePart = $parts[1];
            
            $oidComponents = explode('.', ltrim($oidPart, '.'));
            if (count($oidComponents) < 1) continue;
            
            $ontIndex = end($oidComponents);
            $ontIndexInt = (int)$ontIndex;
            
            if ($ontIndexInt < $portIndex || $ontIndexInt >= $nextPortIndex) {
                continue;
            }
            
            $ontId = $this->snmp->stripTypePrefix(trim($valuePart));
            
            if (empty($ontId)) continue;
            
            $ontData = [
                'port_index' => $portIndex,
                'ont_index' => $ontIndexInt,
                'ont_id' => $ontId,
                'pon_port' => $portName,
                'status' => $statusMap[$ontIndexInt] ?? null,
            ];
            
            if (isset($details[$ontIndexInt])) {
                $ontData = array_merge($ontData, $details[$ontIndexInt]);
            }
            
            if (isset($optical[$ontIndexInt])) {
                $ontData = array_merge($ontData, $optical[$ontIndexInt]);
            }
            
            $onts[] = $ontData;
        }
        return $onts;
    }

    public function getOnus(): array
    {
        $onus = [];
        foreach (self::PORT_MAP as $index => $portName) {
            if (!str_starts_with($portName, 'PON')) continue;
            $portOnts = $this->getOnts($portName);
            foreach ($portOnts as $ont) {
                $onus[] = [
                    'interface' => $ont['pon_port'] . '/' . $ont['ont_id'],
                    'ont_id' => $ont['ont_id'],
                    'name' => $ont['ont_id'],
                    'vendor' => $ont['vendor'] ?? null,
                    'model' => $ont['model'] ?? null,
                    'serial_number' => $ont['serial_number'] ?? null,
                    'mac_address' => $ont['oui'] ?? null,
                    'rx_power' => $ont['rx_power'] ?? null,
                    'tx_power' => $ont['tx_power'] ?? null,
                    'voltage' => $ont['voltage'] ?? null,
                    'temperature' => $ont['temperature'] ?? null,
                    'status' => $ont['status'] ?? null,
                ];
            }
        }
        return $onus;
    }

    public function getOntDetail(string $ontId): array
    {
        $detail = ['ont_id' => $ontId];
        $raw = $this->snmp->walk('.1.3.6.1.4.1.50224.3.12.2');
        
        $current = [];
        foreach ($raw as $line) {
            preg_match('/\.(\d+)\.(\d+)\s*=\s*(.*)/', $line, $m);
            if (!isset($m[1])) continue;
            
            $tableIdx = (int)$m[1];
            $fieldIdx = (int)$m[2];
            $value = trim($m[3]);
            
            if (!isset($current[$tableIdx])) {
                $current[$tableIdx] = [];
            }
            
            if ($fieldIdx === 2) {
                preg_match('/STRING:\s*"(.+?)"/', $value, $v);
                $current[$tableIdx]['name'] = $v[1] ?? '';
            } elseif ($fieldIdx === 8) {
                preg_match('/STRING:\s*"(.+?)"/', $value, $v);
                $current[$tableIdx]['vendor'] = trim($v[1] ?? '');
            } elseif ($fieldIdx === 9) {
                preg_match('/STRING:\s*"(.+?)"/', $value, $v);
                $current[$tableIdx]['model'] = trim($v[1] ?? '');
            } elseif ($fieldIdx === 10) {
                preg_match('/STRING:\s*"(.+?)"/', $value, $v);
                $current[$tableIdx]['oui'] = trim($v[1] ?? '');
            } elseif ($fieldIdx === 11) {
                preg_match('/STRING:\s*"(.+?)"/', $value, $v);
                $current[$tableIdx]['firmware'] = trim($v[1] ?? '');
            }
        }
        
        foreach ($current as $entry) {
            if (($entry['name'] ?? '') === $ontId) {
                return array_merge($detail, $entry);
            }
        }
        
        return $detail;
    }

    public function getOntOpticalInfo(string $ontId): array
    {
        return [];
    }

    public function getOntTraffic(string $ontId): array
    {
        return ['rx_bytes' => 0, 'tx_bytes' => 0, 'rx_packets' => 0, 'tx_packets' => 0];
    }

    public function rebootOnt(string $ontId): bool
    {
        if (!$this->writeCommunity) return false;
        return false;
    }

    public function getSystemResources(): array
    {
        return [
            'cpu_usage' => (int)($this->snmp->get(self::OID_SYS['cpu_usage']) ?? 0),
            'memory_usage' => (int)($this->snmp->get(self::OID_SYS['mem_usage']) ?? 0),
            'temperature' => (int)($this->snmp->get(self::OID_SYS['temp']) ?? 0),
        ];
    }

    public function getAlarms(): array
    {
        return [];
    }

    public function testConnection(): bool
    {
        $result = $this->snmp->get(self::OID_SYS['model_name']);
        return !empty($result);
    }
}