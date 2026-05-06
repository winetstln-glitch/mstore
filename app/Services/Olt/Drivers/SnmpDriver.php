<?php

namespace App\Services\Olt\Drivers;

use App\Models\Olt;
use App\Services\Olt\OltDriverInterface;
use Exception;
use Illuminate\Support\Facades\Log;

class SnmpDriver implements OltDriverInterface
{
    protected $olt;
    protected $community;
    protected $port;
    protected $timeout;
    protected $retries;

    protected $brandProfiles = [
        'hioso' => [
            [
                'name' => 'HIOSO_EPON_C',
                'status_table' => '1.3.6.1.4.1.25355.3.2.6.3.2.1.39',
                'name_table'   => '1.3.6.1.4.1.25355.3.2.6.3.2.1.37',
                'sn_table'     => '1.3.6.1.4.1.25355.3.2.6.3.2.1.11',
                'rx_power_table' => '1.3.6.1.4.1.25355.3.2.6.14.2.1.8',
            ],
            [
                'name' => 'HIOSO_EPON_B',
                'status_table' => '1.3.6.1.4.1.3320.101.10.1.1.26',
                'name_table'   => '1.3.6.1.4.1.3320.101.10.1.1.79',
                'sn_table'     => '1.3.6.1.4.1.3320.101.10.1.1.3',
                'rx_power_table' => '1.3.6.1.4.1.3320.101.10.5.1.6',
            ],
        ],
        'hsgq' => [
            [
                'name' => 'HSGQ_EPON',
                'status_table' => '1.3.6.1.4.1.3320.101.10.1.1.26',
                'name_table'   => '1.3.6.1.4.1.3320.101.10.1.1.79',
                'sn_table'     => '1.3.6.1.4.1.3320.101.10.1.1.3',
                'rx_power_table' => '1.3.6.1.4.1.3320.101.10.5.1.6',
            ],
        ],
        'zte' => [
            [
                'name' => 'ZTE_C300_NEW',
                'status_table' => '1.3.6.1.4.1.3902.1012.3.28.1.1.2',
                'name_table'   => '1.3.6.1.4.1.3902.1012.3.28.1.1.3',
                'sn_table'     => '1.3.6.1.4.1.3902.1012.3.28.1.1.5',
                'rx_power_table' => '1.3.6.1.4.1.3902.1012.3.28.2.1.4',
            ],
            [
                'name' => 'ZTE_C220',
                'status_table' => '1.3.6.1.4.1.3902.1015.1010.5.1.1.2',
                'name_table'   => '1.3.6.1.4.1.3902.1015.1010.5.1.1.3',
            ]
        ],
        'vsol' => [
            [
                'name' => 'VSOL_EPON',
                'status_table' => '1.3.6.1.4.1.37950.1.1.5.13.1.1.4',
                'name_table'   => '1.3.6.1.4.1.37950.1.1.5.13.1.1.10',
                'sn_table'     => '1.3.6.1.4.1.37950.1.1.5.13.1.1.2',
                'rx_power_table' => '1.3.6.1.4.1.37950.1.1.5.13.1.1.21',
            ],
        ],
        'huawei' => [
            [
                'name' => 'HUAWEI_GPON',
                'status_table' => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.9',
                'name_table'   => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.3',
                'sn_table'     => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.1',
                'rx_power_table' => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.4',
            ],
            [
                'name' => 'HUAWEI_EPON',
                'status_table' => '1.3.6.1.4.1.2011.6.128.1.1.2.45.1.4',
                'name_table'   => '1.3.6.1.4.1.2011.6.128.1.1.2.45.1.3',
            ]
        ],
        'cdata' => [
            [
                'name' => 'CDATA_EPON',
                'status_table' => '1.3.6.1.4.1.34592.1.3.100.12.1.1.1.15',
                'name_table'   => '1.3.6.1.4.1.34592.1.3.100.12.1.1.1.10',
                'sn_table'     => '1.3.6.1.4.1.34592.1.3.100.12.1.1.1.10',
                'rx_power_table' => '1.3.6.1.4.1.34592.1.3.100.12.1.1.1.21',
            ]
        ]
    ];

    public function connect(Olt $olt, $timeout = 10)
    {
        if (!extension_loaded('snmp')) {
            throw new Exception('PHP SNMP extension is not loaded.');
        }

        $this->olt = $olt;
        $this->community = $olt->snmp_community ?: 'public';
        $this->port = $olt->snmp_port ?: 161;
        $this->timeout = $timeout * 1000000; // microseconds
        $this->retries = 1;

        // Test connection
        $sysDescr = @snmpget($this->olt->host.':'.$this->port, $this->community, '1.3.6.1.2.1.1.1.0', $this->timeout, $this->retries);
        if ($sysDescr === false) {
            throw new Exception('Could not connect to OLT via SNMP.');
        }

        return true;
    }

    public function getOnus()
    {
        $brand = strtolower($this->olt->brand);
        $profiles = $this->brandProfiles[$brand] ?? null;

        if (!$profiles) {
            throw new Exception("SNMP Profile for brand {$brand} not found.");
        }

        $onus = [];
        foreach ($profiles as $profile) {
            try {
                $profileOnus = $this->fetchOnusFromProfile($profile);
                if (!empty($profileOnus)) {
                    $onus = array_merge($onus, $profileOnus);
                }
            } catch (Exception $e) {
                Log::warning("SNMP Fetch failed for profile {$profile['name']}: " . $e->getMessage());
            }
        }

        return $onus;
    }

    protected function fetchOnusFromProfile($profile)
    {
        $onus = [];
        $host = $this->olt->host . ':' . $this->port;

        Log::info("SNMP Sync: Fetching ONUs for profile {$profile['name']} on {$host}");

        // 1. Walk status table to get indices and status
        $statusRaw = @snmprealwalk($host, $this->community, $profile['status_table'], $this->timeout, $this->retries);
        if ($statusRaw === false) {
            Log::warning("SNMP Sync: Status table walk failed for {$profile['name']}");
            return [];
        }

        Log::info("SNMP Sync: Found " . count($statusRaw) . " entries in status table");

        // 2. Walk name table
        $namesRaw = [];
        if (isset($profile['name_table'])) {
            $namesRaw = @snmprealwalk($host, $this->community, $profile['name_table'], $this->timeout, $this->retries) ?: [];
        }

        // 3. Walk SN table
        $snsRaw = [];
        if (isset($profile['sn_table'])) {
            $snsRaw = @snmprealwalk($host, $this->community, $profile['sn_table'], $this->timeout, $this->retries) ?: [];
        }

        // 4. Walk RX Power table
        $rxPowerRaw = [];
        if (isset($profile['rx_power_table'])) {
            $rxPowerRaw = @snmprealwalk($host, $this->community, $profile['rx_power_table'], $this->timeout, $this->retries) ?: [];
        }

        foreach ($statusRaw as $oid => $val) {
            $idx = $this->extractIndex($oid, $profile['status_table']);
            
            $statusValue = $this->cleanValue($val);
            $name = $this->cleanValue($this->getByIndex($namesRaw, $idx, $profile['name_table'] ?? ''));
            $sn = $this->cleanValue($this->getByIndex($snsRaw, $idx, $profile['sn_table'] ?? ''));
            $rxPower = $this->cleanValue($this->getByIndex($rxPowerRaw, $idx, $profile['rx_power_table'] ?? ''));

            $onus[] = [
                'interface' => $this->formatInterface($idx, $profile['name']),
                'name' => $name,
                'serial_number' => $this->formatSn($sn),
                'status' => $this->mapStatus($statusValue, $this->olt->brand),
                'signal' => $this->formatSignal($rxPower, $this->olt->brand),
                'description' => "Synced via SNMP ({$profile['name']})"
            ];
        }

        return $onus;
    }

    protected function extractIndex($oid, $baseOid)
    {
        $oid = ltrim($oid, '.');
        $baseOid = ltrim($baseOid, '.');
        
        // Remove the base OID from the start of the OID to get the index
        if (strpos($oid, $baseOid) === 0) {
            return ltrim(substr($oid, strlen($baseOid)), '.');
        }

        // Fallback: If baseOid is not at the start, try to find where it is
        $pos = strpos($oid, $baseOid);
        if ($pos !== false) {
            return ltrim(substr($oid, $pos + strlen($baseOid)), '.');
        }

        // Last resort: return the last part of the OID
        $parts = explode('.', $oid);
        return end($parts);
    }

    protected function getByIndex($rawMap, $idx, $baseOid)
    {
        if (empty($rawMap)) return null;

        $idx = ltrim($idx, '.');
        $baseOid = ltrim($baseOid, '.');

        // Try exact match with base OID
        $targetOid = $baseOid . '.' . $idx;
        if (isset($rawMap[$targetOid])) return $rawMap[$targetOid];
        if (isset($rawMap['.' . $targetOid])) return $rawMap['.' . $targetOid];

        // Try searching by suffix (most reliable if base OID varies slightly)
        foreach ($rawMap as $oid => $val) {
            $cleanOid = ltrim($oid, '.');
            if (str_ends_with($cleanOid, '.' . $idx)) {
                return $val;
            }
        }
        return null;
    }

    protected function cleanValue($val)
    {
        if ($val === null) return null;
        $val = str_replace(['STRING: ', 'INTEGER: ', 'Hex-STRING: ', 'Gauge32: ', '"'], '', $val);
        return trim($val);
    }

    protected function formatInterface($idx, $profileName)
    {
        $idx = (string)$idx;
        if (strpos($profileName, 'HIOSO') !== false || strpos($profileName, 'HSGQ') !== false) {
            if (strpos($idx, '.') !== false) {
                $parts = explode('.', $idx);
                if (count($parts) >= 2) {
                    $onu = (int)end($parts);
                    $port = (int)$parts[count($parts) - 2];
                    return "0/$port:$onu";
                }
            }
            
            $intIdx = (int)$idx;
            $port = ($intIdx >> 16) & 0xff;
            if ($port === 0 || $port > 16) $port = ($intIdx >> 8) & 0xff;
            $onu = $intIdx & 0xff;
            if ($port !== 0) return "0/$port:$onu";
        }

        if (strpos($profileName, 'HUAWEI') !== false) {
            // Huawei GPON index decoding can be complex, for now return as is
            return "GPON " . $idx;
        }

        return $idx;
    }

    protected function formatSn($sn)
    {
        if (!$sn) return 'N/A';
        // Handle hex strings
        if (preg_match('/^[0-9A-Fa-f\s]+$/', $sn)) {
            $hex = str_replace(' ', '', $sn);
            if (strlen($hex) >= 8) return strtoupper($hex);
        }
        return strtoupper($sn);
    }

    protected function mapStatus($val, $brand)
    {
        $val = (int)$val;
        $brand = strtolower($brand);

        // Mapping logic based on genieacs oltService.js
        $onlineValues = [
            'hioso' => [1, 3, 4],
            'hsgq'  => [1, 3, 4],
            'vsol'  => [1],
            'huawei' => [5],
            'cdata' => [1, 3],
        ];

        $allowed = $onlineValues[$brand] ?? [1, 3];
        return in_array($val, $allowed) ? 'online' : 'offline';
    }

    protected function formatSignal($val, $brand)
    {
        if ($val === null || $val === '') return null;
        $n = (float)$val;
        if ($n == 0 || $n == 65535) return null;

        // Heuristic conversion to dBm
        if ($n > 0) {
            if ($n > 1000) return round($n / 100, 2);
            if ($n > 100) return round($n / 10, 2);
        }
        if ($n < -100) return round($n / 10, 2);
        
        return $n;
    }

    public function getSystemInfo()
    {
        $brand = strtolower($this->olt->brand);
        $oids = [
            'hioso' => ['temp' => '1.3.6.1.4.1.25355.3.2.1.1.1.0', 'cpu' => '1.3.6.1.4.1.25355.3.2.1.1.2.0'],
            'huawei' => ['temp' => '1.3.6.1.4.1.2011.6.128.1.1.2.2.1.2.0', 'cpu' => '1.3.6.1.4.1.2011.6.128.1.1.2.2.1.3.0'],
        ];

        $brandOids = $oids[$brand] ?? $oids['hioso'];
        $host = $this->olt->host . ':' . $this->port;

        $temp = $this->cleanValue(@snmpget($host, $this->community, $brandOids['temp'], $this->timeout, $this->retries));
        $cpu = $this->cleanValue(@snmpget($host, $this->community, $brandOids['cpu'], $this->timeout, $this->retries));

        return [
            'uptime' => $this->cleanValue(@snmpget($host, $this->community, '1.3.6.1.2.1.1.3.0', $this->timeout, $this->retries)),
            'version' => $this->cleanValue(@snmpget($host, $this->community, '1.3.6.1.2.1.1.1.0', $this->timeout, $this->retries)),
            'temperature' => $temp,
            'cpu_usage' => $cpu,
        ];
    }

    public function disconnect()
    {
        // SNMP is stateless in PHP
        return true;
    }
}
