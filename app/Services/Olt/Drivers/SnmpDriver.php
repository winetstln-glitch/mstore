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
            'HIOSO_C' => [
                'name' => 'HIOSO_C',
                'oid_name' => '1.3.6.1.4.1.25355.3.2.6.3.2.1.37',
                'oid_status' => '1.3.6.1.4.1.25355.3.2.6.3.2.1.39',
                'oid_tx' => '1.3.6.1.4.1.25355.3.2.6.14.2.1.4',
                'oid_rx' => '1.3.6.1.4.1.25355.3.2.6.14.2.1.8',
                'oid_mac' => '1.3.6.1.4.1.25355.3.2.6.3.2.1.11',
                'oid_vlan_pvid' => '1.3.6.1.4.1.25355.3.2.6.5.1.1.2',
                'divider' => 1,
            ],
            'HIOSO_B' => [
                'name' => 'HIOSO_B',
                'oid_name' => '1.3.6.1.4.1.3320.101.10.1.1.79',
                'oid_status' => '1.3.6.1.4.1.3320.101.10.1.1.26',
                'oid_tx' => '1.3.6.1.4.1.3320.101.10.5.1.5',
                'oid_rx' => '1.3.6.1.4.1.3320.101.10.5.1.6',
                'oid_mac' => '1.3.6.1.4.1.3320.101.10.1.1.3',
                'divider' => 10,
            ],
            'HIOSO_GPON' => [
                'name' => 'HIOSO_GPON',
                'oid_name' => '1.3.6.1.4.1.25355.3.3.1.1.1.2',
                'oid_status' => '1.3.6.1.4.1.25355.3.3.1.1.1.11',
                'oid_tx' => '1.3.6.1.4.1.25355.3.3.1.1.4.1.2',
                'oid_rx' => '1.3.6.1.4.1.25355.3.3.1.1.4.1.1',
                'oid_mac' => '1.3.6.1.4.1.25355.3.3.1.1.1.5',
                'oid_vlan_pvid' => '1.3.6.1.4.1.25355.3.3.1.2.2.1.2',
                'divider' => 100,
            ],
            'HIOSO_HA73' => [
                'name' => 'HIOSO_HA73',
                'oid_name' => '1.3.6.1.4.1.34592.1.3.100.12.1.1.2',
                'oid_status' => '1.3.6.1.4.1.34592.1.3.100.12.1.1.5',
                'oid_tx' => '1.3.6.1.4.1.34592.1.3.100.12.1.1.13',
                'oid_rx' => '1.3.6.1.4.1.34592.1.3.100.12.1.1.14',
                'oid_mac' => '1.3.6.1.4.1.34592.1.3.100.12.1.1.12',
                'divider' => 10,
            ],
        ],
        'hsgq' => [
            'HSGQ' => [
                'name' => 'HSGQ',
                'oid_name' => '1.3.6.1.4.1.50224.3.12.2.1.2',
                'oid_rx' => '1.3.6.1.4.1.50224.3.12.3.1.4',
                'oid_tx' => '1.3.6.1.4.1.50224.3.12.3.1.8',
                'divider' => 'hsgq',
            ],
            'HSGQ_GPON' => [
                'name' => 'HSGQ_GPON',
                'oid_name' => '1.3.6.1.4.1.55047.1.3.2.1.2.1.5',
                'oid_status' => '1.3.6.1.4.1.55047.1.3.2.1.2.1.13',
                'oid_tx' => '1.3.6.1.4.1.55047.1.3.2.1.2.1.19',
                'oid_rx' => '1.3.6.1.4.1.55047.1.3.2.1.2.1.20',
                'oid_mac' => '1.3.6.1.4.1.55047.1.3.2.1.2.1.2',
                'divider' => 100,
            ],
        ],
        'zte' => [
            'ZTE' => [
                'name' => 'ZTE',
                'oid_name' => '1.3.6.1.4.1.3902.1012.3.28.1.1.2',
                'oid_sn' => '1.3.6.1.4.1.3902.1012.3.28.1.1.5',
                'oid_status' => '1.3.6.1.4.1.3902.1012.3.28.2.1.4',
                'oid_tx' => '1.3.6.1.4.1.3902.1012.3.50.12.1.1.9',
                'oid_rx' => '1.3.6.1.4.1.3902.1012.3.50.12.1.1.10',
                'oid_mac' => '1.3.6.1.4.1.3902.1012.3.28.1.1.5',
                'divider' => 'zte',
            ],
        ],
        'vsol' => [
            'VSOL_EPON' => [
                'name' => 'VSOL_EPON',
                'oid_status' => '1.3.6.1.4.1.37950.1.1.5.13.1.1.4',
                'oid_name' => '1.3.6.1.4.1.37950.1.1.5.13.1.1.10',
                'oid_sn' => '1.3.6.1.4.1.37950.1.1.5.13.1.1.2',
                'oid_rx' => '1.3.6.1.4.1.37950.1.1.5.13.1.1.21',
                'divider' => 1,
            ],
        ],
        'huawei' => [
            'Huawei' => [
                'name' => 'Huawei',
                'oid_name' => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.9',
                'oid_status' => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.10',
                'oid_tx' => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.3',
                'oid_rx' => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.4',
                'oid_mac' => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.1',
                'oid_sn' => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.3',
                'divider' => 100,
            ],
        ],
        'cdata' => [
            'CDATA_EPON' => [
                'name' => 'CDATA_EPON',
                'oid_status' => '1.3.6.1.4.1.34592.1.3.100.12.1.1.1.15',
                'oid_name' => '1.3.6.1.4.1.34592.1.3.100.12.1.1.1.10',
                'oid_sn' => '1.3.6.1.4.1.34592.1.3.100.12.1.1.1.10',
                'oid_rx' => '1.3.6.1.4.1.34592.1.3.100.12.1.1.1.21',
                'divider' => 1,
            ],
        ],
    ];

    public function connect(Olt $olt, $timeout = 10)
    {
        if (!extension_loaded('snmp')) {
            throw new Exception('PHP SNMP extension tidak ter-load! Aktifkan di php.ini dengan menghapus tanda ";" di depan "extension=snmp".');
        }

        $this->olt = $olt;
        $this->community = $olt->snmp_community ?: 'public';
        $this->port = $olt->snmp_port ?: 161;
        $this->timeout = $timeout * 1000000; // microseconds
        $this->retries = 3;

        $sysDescr = @snmpget($this->olt->host.':'.$this->port, $this->community, '1.3.6.1.2.1.1.1.0', $this->timeout, $this->retries);
        if ($sysDescr === false) {
            throw new Exception('Tidak bisa terhubung ke OLT via SNMP. Periksa Community, Port, dan konfigurasi OLT.');
        }

        return true;
    }

    public function getOnus()
    {
        $brand = strtolower($this->olt->brand);
        $profiles = $this->brandProfiles[$brand] ?? [];

        $debug = true;
        
        if ($debug) {
            Log::info("[OLT SYNC] DEBUG MODE ACTIVE: Trying ALL profiles from ALL brands");
            $allProfiles = [];
            foreach ($this->brandProfiles as $b => $brandProfiles) {
                $allProfiles = array_merge($allProfiles, $brandProfiles);
            }
            Log::info("[OLT SYNC] DEBUG: Total profiles to try: " . count($allProfiles));
            
            foreach ($allProfiles as $profile) {
                Log::info("[OLT SYNC] DEBUG: Trying profile: " . $profile['name']);
                try {
                    $onus = $this->fetchOnusFromProfile($profile);
                    if (count($onus) > 0) {
                        Log::info("[OLT SYNC] DEBUG: SUCCESS! Profile " . $profile['name'] . " found " . count($onus) . " ONUs!");
                        $this->olt->update(['last_profile' => $profile['name']]);
                        return $onus;
                    }
                } catch (\Exception $e) {
                    Log::warning("[OLT SYNC] DEBUG: Profile " . $profile['name'] . " failed: " . $e->getMessage());
                }
            }
            
            Log::warning("[OLT SYNC] DEBUG: No profile worked! Trying simple snmpwalk of OID 1.3.6.1 to find available OIDs");
            $host = $this->olt->host . ':' . $this->port;
            $rawAll = @snmprealwalk($host, $this->community, '1.3.6.1', $this->timeout, $this->retries);
            if ($rawAll !== false) {
                Log::info("[OLT SYNC] DEBUG: Found " . count($rawAll) . " top-level OIDs");
                Log::info("[OLT SYNC] DEBUG: Sample OIDs found: " . json_encode(array_slice(array_keys($rawAll), 0, 30)));
            }
        }

        if (empty($profiles)) {
            throw new Exception("SNMP Profile for brand {$brand} not found.");
        }

        $activeProfile = null;
        $cachedProfileName = $this->olt->last_profile;

        if ($cachedProfileName && isset($profiles[$cachedProfileName])) {
            $activeProfile = $profiles[$cachedProfileName];
            Log::info("[OLT SYNC] Menggunakan cached profile: {$cachedProfileName}");
        } else {
            Log::info("[OLT SYNC] Profile \"{$cachedProfileName}\" tidak dikenali, mulai auto-probe...");
            $host = $this->olt->host . ':' . $this->port;
            
            foreach ($profiles as $pName => $pMap) {
                try {
                    $testOid = $pMap['oid_name'];
                    $result = @snmpgetnext($host, $this->community, $testOid, $this->timeout, $this->retries);
                    if ($result !== false) {
                        $activeProfile = $pMap;
                        $activeProfile['pName'] = $pName;
                        Log::info("[OLT SYNC] Active Profile: {$pName}");
                        
                        $this->olt->update(['last_profile' => $pName]);
                        break;
                    }
                } catch (Exception $e) {
                    Log::warning("[OLT SYNC] Probe failed for profile {$pName}: " . $e->getMessage());
                }
            }
        }

        if (!$activeProfile) {
            throw new Exception("Gagal mendeteksi profil OLT. Pastikan community string dan host benar.");
        }

        return $this->fetchOnusFromProfile($activeProfile);
    }

    protected function fetchOnusFromProfile($profile)
    {
        $onus = [];
        $host = $this->olt->host . ':' . $this->port;
        $isHsgq = ($profile['name'] ?? '') === 'HSGQ';

        $activeOIDs = [];
        foreach (['name', 'status', 'tx', 'rx', 'mac', 'sn'] as $k) {
            $oidKey = 'oid_' . $k;
            if (isset($profile[$oidKey])) {
                $activeOIDs[$k] = $profile[$oidKey];
            }
        }

        $dataStore = [];
        $categories = array_keys($activeOIDs);

        foreach ($categories as $cat) {
            $baseOid = $activeOIDs[$cat];
            Log::info("[OLT SYNC] Trying snmprealwalk for category {$cat} with OID: {$baseOid}");
            
            $rawData = @snmprealwalk($host, $this->community, $baseOid, $this->timeout, $this->retries);
            
            if ($rawData === false) {
                Log::warning("[OLT SYNC] snmprealwalk FAILED for category {$cat} (OID: {$baseOid}) - error: " . error_get_last()['message'] ?? 'unknown');
                $dataStore[$cat] = [];
                continue;
            }

            Log::info("[OLT SYNC] snmprealwalk SUCCESS for category {$cat} - found " . count($rawData) . " entries");
            Log::info("[OLT SYNC] Sample entries for {$cat}: " . json_encode(array_slice($rawData, 0, 5)));

            $dataStore[$cat] = [];
            foreach ($rawData as $oid => $value) {
                $cleanOid = ltrim($oid, '.');
                $cleanBaseOid = ltrim($baseOid, '.');
                
                if (strpos($cleanOid, $cleanBaseOid) === 0) {
                    $idx = ltrim(substr($cleanOid, strlen($cleanBaseOid)), '.');
                    
                    if ($isHsgq) {
                        if (str_ends_with($idx, '.65535.65535')) {
                            continue;
                        }
                        if (str_ends_with($idx, '.0.0')) {
                            $idx = substr($idx, 0, -4);
                        }
                    }
                    
                    $dataStore[$cat][$idx] = $this->cleanValue($value);
                }
            }
            
            Log::info("[OLT SYNC] Parsed entries for {$cat}: " . count($dataStore[$cat]));
        }

        Log::info("[OLT SYNC] Walk selesai. Entry per OID: " . json_encode(array_map(fn($c) => count($dataStore[$c]), $categories)));

        $nameData = $dataStore['name'] ?? [];
        if (empty($nameData)) {
            Log::warning("[OLT SYNC] No name data found, trying to use other OID as key");
            $nameData = $dataStore['status'] ?? $dataStore['rx'] ?? [];
        }

        foreach ($nameData as $idx => $rawName) {
            $name = preg_replace('/[^\x20-\x7E]/', '', (string)($dataStore['name'][$idx] ?? $rawName));
            $name = trim($name);
            if (empty($name) || in_array(strtolower($name), ['public', 'internal', 'private'], true)) {
                $name = "ONU-{$idx}";
            }

            $status = 'Down';
            if ($isHsgq) {
                $rxRaw = $dataStore['rx'][$idx] ?? null;
                $status = ($rxRaw !== null && (int)$rxRaw > -4000) ? 'Up' : 'Down';
            } else {
                $isGPON = in_array($profile['name'] ?? '', ['HIOSO_GPON', 'HSGQ_GPON'])
                    || strpos($profile['oid_name'] ?? '', '.25355.3.3') !== false
                    || strpos($profile['oid_name'] ?? '', '.55047.1.3') !== false;
                $sVal = $dataStore['status'][$idx] ?? null;
                if ($sVal !== null) {
                    $v = (int)$sVal;
                    if (($profile['name'] ?? '') === 'ZTE') {
                        $status = ($v === 3) ? 'Up' : 'Down';
                    } elseif (($profile['name'] ?? '') === 'HSGQ_GPON') {
                        $status = ($v === 1) ? 'Up' : 'Down';
                    } elseif ($isGPON) {
                        $status = ($v >= 2 && $v <= 4) ? 'Up' : 'Down';
                    } else {
                        $status = in_array($v, [1, 3, 4], true) ? 'Up' : 'Down';
                    }
                }
            }

            $txPower = $this->parseSignal($dataStore['tx'][$idx] ?? null, $profile);
            $rxPower = $this->parseSignal($dataStore['rx'][$idx] ?? null, $profile);

            $onus[] = [
                'interface' => $this->formatInterface($idx, $profile['name'] ?? ''),
                'onu_index' => $idx,
                'name' => $name,
                'serial_number' => $this->formatSn($dataStore['sn'][$idx] ?? ''),
                'sn' => $this->formatSn($dataStore['sn'][$idx] ?? ''),
                'status' => strtolower($status) === 'up' ? 'online' : 'offline',
                'signal' => $rxPower,
                'rx_power' => $rxPower,
                'tx_power' => $txPower,
                'mac' => $this->formatMac($dataStore['mac'][$idx] ?? ''),
                'description' => "Synced via SNMP ({$profile['name']})"
            ];
        }

        Log::info("[OLT SYNC] Total ONU: " . count($onus));
        return $onus;
    }

    protected function getOidFromValue($value, $baseOid)
    {
        if (is_string($value) && preg_match('/^(?:STRING|INTEGER|Hex-STRING|Gauge32):\s*\.?([\d.]+)/', $value, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function extractIndex($oid, $baseOid)
    {
        $oid = ltrim($oid, '.');
        $baseOid = ltrim($baseOid, '.');
        
        if (strpos($oid, $baseOid) === 0) {
            return ltrim(substr($oid, strlen($baseOid)), '.');
        }

        $pos = strpos($oid, $baseOid);
        if ($pos !== false) {
            return ltrim(substr($oid, $pos + strlen($baseOid)), '.');
        }

        $parts = explode('.', $oid);
        return end($parts);
    }

    protected function cleanValue($val)
    {
        if ($val === null) return null;
        $val = str_replace(['STRING: ', 'INTEGER: ', 'Hex-STRING: ', 'Gauge32: ', '"'], '', $val);
        return trim($val);
    }

    protected function parseSignal($val, $profile)
    {
        if ($val === null || $val === '') return null;
        $num = (float)$val;
        if (is_nan($num) || $num === 0.0 || $num === 65535.0 || $num === -65535.0) {
            return null;
        }

        $divider = $profile['divider'] ?? 1;
        if ($divider === 'zte') {
            return round(($num - 15000) / 500, 2);
        }
        if ($divider === 'hsgq') {
            return $num < 0 ? round($num / 100, 2) : round($num / 1000, 2);
        }

        if (abs($num) > 500 && $divider === 1) {
            return round($num / 100, 2);
        }

        return round($num / $divider, 2);
    }

    protected function formatMac($val)
    {
        if (!$val) return '';
        
        if (is_string($val)) {
            $hex = preg_replace('/[^0-9A-Fa-f]/', '', $val);
            if (strlen($hex) === 12) {
                return strtoupper(implode(':', str_split($hex, 2)));
            }
        }
        
        return strtoupper(trim((string)$val));
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

        if (strpos($profileName, 'Huawei') !== false || strpos($profileName, 'HUAWEI') !== false) {
            return "GPON " . $idx;
        }

        return $idx;
    }

    protected function formatSn($sn)
    {
        if (!$sn) return 'N/A';
        if (preg_match('/^[0-9A-Fa-f\s]+$/', $sn)) {
            $hex = str_replace(' ', '', $sn);
            if (strlen($hex) >= 8) return strtoupper($hex);
        }
        return strtoupper($sn);
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
        return true;
    }
}
