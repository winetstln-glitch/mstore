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
    protected array $cachedPorts = [];

    /*
    |--------------------------------------------------------------------------
    | SYSTEM OID
    |--------------------------------------------------------------------------
    */

    const OID_SYS = [
        'model_name'   => '.1.3.6.1.4.1.17409.2.3.1.2.1.1.2.1',
        'uptime'       => '.1.3.6.1.4.1.17409.2.3.1.2.1.1.5.1',
        'vendor'       => '.1.3.6.1.4.1.17409.2.3.1.2.1.1.10.1',
        'serial'       => '.1.3.6.1.4.1.17409.2.3.1.3.1.1.12.1.0',
        'firmware'     => '.1.3.6.1.4.1.17409.2.3.1.3.1.1.15.1.0',
        'firmware_ver' => '.1.3.6.1.4.1.17409.2.3.1.3.1.1.9.1.0',
        'sys_uptime'   => '.1.3.6.1.4.1.17409.2.3.1.3.1.1.10.1.0',
    ];

    /*
    |--------------------------------------------------------------------------
    | ONU OID
    |--------------------------------------------------------------------------
    */

    const OID_ONT = [
        'name'            => '.1.3.6.1.4.1.17409.2.8.4.1.1.2',
        'serial_number'   => '.1.3.6.1.4.1.17409.2.8.4.1.1.3',
        'status'          => '.1.3.6.1.4.1.17409.2.8.4.1.1.7',
        'vendor'          => '.1.3.6.1.4.1.17409.2.8.4.1.1.5',
        'model'           => '.1.3.6.1.4.1.17409.2.8.4.1.1.6',
        'rx_power'        => '.1.3.6.1.4.1.17409.2.8.4.4.1.4',
        'last_down_cause' => '.1.3.6.1.4.1.17409.2.8.4.1.1.103',
    ];

    /*
    |--------------------------------------------------------------------------
    | PORT CONFIG
    |--------------------------------------------------------------------------
    |
    | PORT_INDEX_BASE = 0x480000 = 4718592
    | PORT_INDEX_STEP = 0x1000  = 4096
    |
    | PON01 index = 4718592 (0x480000)
    | PON02 index = 4722688 (0x481000)
    | PON08 index = 4761600 (0x487000)
    |
    | ONU index   = PORT_INDEX_BASE + (port-1)*STEP + onu_id
    | Contoh:     ONU 1 di PON01 = 4718593 (0x480001)
    |
    */

    const PORT_COUNT     = 8;
    const PORT_INDEX_BASE = 4718592;
    const PORT_INDEX_STEP = 4096;

    /*
    |--------------------------------------------------------------------------
    | ONU STATUS MAP
    |--------------------------------------------------------------------------
    |
    | Berdasarkan CData MIB:
    | 1  = online       6  = lofi
    | 2  = offline      7  = sfi
    | 3  = dyinggasp    8  = loki
    | 4  = los          9  = act
    | 5  = losi        10  = rst
    |
    */

    const ONU_STATUS_MAP = [
        1  => 'online',
        2  => 'offline',
        3  => 'dyinggasp',
        4  => 'los',
        5  => 'losi',
        6  => 'lofi',
        7  => 'sfi',
        8  => 'loki',
        9  => 'act',
        10 => 'rst',
    ];

    /*
    |--------------------------------------------------------------------------
    | ONU NAME PATTERN
    |--------------------------------------------------------------------------
    |
    | Format default CData: "gpon 0/0/X/Y"
    | X = port number (1-8)
    | Y = ONU ID pada port tersebut
    |
    */

    const ONU_NAME_PATTERN = '/^gpon\s+0\/0\/(\d+)\/(\d+)$/i';

    /*
    |--------------------------------------------------------------------------
    | CONSTRUCTOR
    |--------------------------------------------------------------------------
    */

    public function __construct(
        string $ip,
        string $readCommunity = 'public',
        ?string $writeCommunity = null
    ) {
        $this->ip             = $ip;
        $this->readCommunity  = $readCommunity;
        $this->writeCommunity = $writeCommunity;

        // Fix #11: Konsisten timeout dengan connect()
        $this->snmp = new SNMPHelper(
            $ip,
            $readCommunity,
            $writeCommunity,
            3,  // timeout
            0   // retries
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONNECT / DISCONNECT
    |--------------------------------------------------------------------------
    */

    public function connect($olt, int $timeout = 5): void
    {
        $this->oltInstance     = $olt;
        $this->ip              = $olt->ip_address     ?? $this->ip;
        $this->readCommunity   = $olt->read_community  ?? $this->readCommunity;
        $this->writeCommunity  = $olt->write_community ?? $this->writeCommunity;

        $this->snmp = new SNMPHelper(
            $this->ip,
            $this->readCommunity,
            $this->writeCommunity,
            3,
            0
        );

        // Fix #9: Reset cache saat re-connect
        $this->cachedPorts = [];

        Log::info("CDataDriver connected to {$this->ip}");
    }

    public function disconnect(): void
    {
        $this->oltInstance = null;
        $this->cachedPorts = [];

        Log::info("CDataDriver disconnected");
    }

    /*
    |--------------------------------------------------------------------------
    | TEST CONNECTION
    |--------------------------------------------------------------------------
    */

    public function testConnection(): bool
    {
        try {
            // Fix #7: Gunakan konstanta, bukan hardcode
            $result = $this->snmp->get(self::OID_SYS['model_name']);

            return !empty($result);
        } catch (\Throwable $e) {
            Log::error('CData testConnection failed: ' . $e->getMessage());
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DEVICE INFO
    |--------------------------------------------------------------------------
    */

    public function getDeviceInfo(): array
    {
        $info = [];

        // Fix #6: Per-field error handling
        foreach (self::OID_SYS as $key => $oid) {
            try {
                $value = $this->snmp->get($oid);

                if (in_array($key, ['uptime', 'sys_uptime'], true)) {
                    $value = $this->snmp->parseTimeticks($value);
                }

                $info[$key] = $value;
            } catch (\Throwable $e) {
                Log::warning("CData getDeviceInfo field [{$key}] failed: " . $e->getMessage());
                $info[$key] = null;
            }
        }

        return $info;
    }

    /*
    |--------------------------------------------------------------------------
    | SYSTEM RESOURCE
    |--------------------------------------------------------------------------
    */

    public function getSystemResources(): array
    {
        return [
            'cpu_usage'    => null,
            'memory_usage' => null,
            'temperature'  => null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PORTS
    |--------------------------------------------------------------------------
    */

    public function getPorts(): array
    {
        if (!empty($this->cachedPorts)) {
            return $this->cachedPorts;
        }

        $ports = [];

        // Fix #8: Gunakan konstanta, bukan magic number
        for ($i = 0; $i < self::PORT_COUNT; $i++) {
            $portNumber = $i + 1;
            $index = self::PORT_INDEX_BASE + ($i * self::PORT_INDEX_STEP);
            $name  = 'PON' . str_pad($portNumber, 2, '0', STR_PAD_LEFT);

            $ports[$name] = [
                'name'         => $name,
                'index'        => $index,
                'type'         => 'pon',
                'rx_bytes'     => 0,
                'tx_bytes'     => 0,
                'admin_status' => 'up',
                'oper_status'  => 'up',
            ];
        }

        $this->cachedPorts = $ports;

        return $ports;
    }

    /*
    |--------------------------------------------------------------------------
    | GET ONUS
    |--------------------------------------------------------------------------
    */

    public function getOnus(): array
    {
        $onus = [];

        // Fix #4: try-catch utama
        try {
            $ports = $this->getPorts();

            /*
            |--------------------------------------------------------------
            | WALK ONU TABLE
            |--------------------------------------------------------------
            */

            $allRaw = $this->snmp->walk(
                '.1.3.6.1.4.1.17409.2.8.4.1.1'
            );

            $ontData = $this->parseOnuTable($allRaw);

            /*
            |--------------------------------------------------------------
            | RX POWER
            |--------------------------------------------------------------
            */

            $rxPowerMap = $this->parseRxPower();

            /*
            |--------------------------------------------------------------
            | BUILD ONU LIST
            |--------------------------------------------------------------
            */

            foreach ($ontData as $ontIndex => $data) {
                if (!isset($data['name'])) {
                    continue;
                }

                $ontName = $data['name'];

                /*
                |----------------------------------------------------------
                | Fix #1: Parse ONU name secara eksplisit
                |----------------------------------------------------------
                |
                | Format: "gpon 0/0/X/Y"
                | - X = port number
                | - Y = ONU ID pada port
                |
                | Sebelumnya pakai str_starts_with yang menyebabkan
                | "gpon 0/0/1" cocok dengan "gpon 0/0/10", "gpon 0/0/11"
                |
                */

                if (!preg_match(self::ONU_NAME_PATTERN, $ontName, $matches)) {
                    Log::debug("CData ONU name tidak match pattern: {$ontName}");
                    continue;
                }

                $portNumber = (int) $matches[1];
                $onuId      = (int) $matches[2];
                $portName   = 'PON' . str_pad($portNumber, 2, '0', STR_PAD_LEFT);

                if (!isset($ports[$portName])) {
                    Log::debug("CData ONU port tidak ditemukan: {$portName} (from name: {$ontName})");
                    continue;
                }

                // Fix #5: Gunakan status map lengkap
                $statusVal = $data['status_val'] ?? 2;
                $status    = self::ONU_STATUS_MAP[$statusVal] ?? 'unknown';

                // Fix #3: ont_id = nomor ONU, bukan nama lengkap
                $onus[] = [
                    'interface'       => "{$portName}/{$onuId}",
                    'ont_id'          => (string) $onuId,
                    'name'            => $ontName,
                    'vendor'          => $data['vendor']          ?? null,
                    'model'           => $data['model']           ?? null,
                    'serial_number'   => $data['serial_number']   ?? null,
                    'mac_address'     => null,
                    'rx_power'        => $rxPowerMap[$ontIndex]   ?? null,
                    'tx_power'        => null,
                    'voltage'         => null,
                    'temperature'     => null,
                    'status'          => $status,
                    'pon_port'        => $portName,
                    'last_down_cause' => $data['last_down_cause'] ?? null, // Fix #10
                ];
            }

            Log::info('CData ONU parsed', ['count' => count($onus)]);

        } catch (\Throwable $e) {
            Log::error('CData getOnus failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $onus;
    }

    /*
    |--------------------------------------------------------------------------
    | PARSE ONU TABLE (extracted method)
    |--------------------------------------------------------------------------
    |
    | Mem-parsing hasil walk .1.3.6.1.4.1.17409.2.8.4.1.1
    |
    | OID format: ...2.8.4.1.1.{field}.{ontIndex}
    | field 2   = name
    | field 3   = serial_number
    | field 5   = vendor
    | field 6   = model
    | field 7   = status
    | field 103 = last_down_cause
    |
    */

    protected function parseOnuTable(array $allRaw): array
    {
        $ontData = [];

        foreach ($allRaw as $line) {
            $parts = explode(' = ', $line, 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$oidPart, $valuePart] = $parts;

            $oidComponents = explode('.', ltrim($oidPart, '.'));

            if (count($oidComponents) < 14) {
                continue;
            }

            $fieldIdx = (int) $oidComponents[12];
            $ontIndex = $oidComponents[13];

            $value = trim($this->snmp->stripTypePrefix($valuePart), '"');

            if (!isset($ontData[$ontIndex])) {
                $ontData[$ontIndex] = [];
            }

            switch ($fieldIdx) {
                case 2:   // ONU Name
                    $ontData[$ontIndex]['name'] = $value;
                    break;

                case 3:   // Serial Number
                    $ontData[$ontIndex]['serial_number'] = $this->parseSerialNumber($value);
                    break;

                case 5:   // Vendor
                    $ontData[$ontIndex]['vendor'] = $value;
                    break;

                case 6:   // Model
                    $ontData[$ontIndex]['model'] = $value;
                    break;

                case 7:   // Status
                    $ontData[$ontIndex]['status_val'] = (int) $value;
                    break;

                case 103: // Last Down Cause (Fix #10)
                    $ontData[$ontIndex]['last_down_cause'] = $value;
                    break;
            }
        }

        return $ontData;
    }

    /*
    |--------------------------------------------------------------------------
    | PARSE RX POWER (extracted method)
    |--------------------------------------------------------------------------
    |
    | CData mengembalikan rx_power sebagai Integer32 dalam satuan 0.01 dBm.
    | Contoh: -1850 → -18.50 dBm
    |
    | OID format: ...2.8.4.4.1.4.{ontIndex}.0.0
    | Index ONU ada di komponen ke-(total-3) dari belakang.
    |
    */

    protected function parseRxPower(): array
    {
        $rxPowerMap = [];

        try {
            $rxPowerRaw = $this->snmp->walk(self::OID_ONT['rx_power']);
        } catch (\Throwable $e) {
            Log::warning('CData RX power walk failed: ' . $e->getMessage());
            return $rxPowerMap;
        }

        foreach ($rxPowerRaw as $line) {
            $parts = explode(' = ', $line, 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$oidPart, $valuePart] = $parts;

            $oidComponents = explode('.', ltrim($oidPart, '.'));

            if (count($oidComponents) < 3) {
                continue;
            }

            // ONU index ada di posisi ke-(count-3) dari belakang
            $ontIndex = $oidComponents[count($oidComponents) - 3];

            $raw = trim($this->snmp->stripTypePrefix($valuePart));

            /*
            |----------------------------------------------------------
            | Fix #2: Handle RX Power parsing dengan benar
            |----------------------------------------------------------
            |
            | SNMP biasanya mengembalikan integer (contoh: -1850)
            | yang perlu dibagi 100 untuk mendapatkan dBm (-18.50).
            |
            | Jika is_numeric langsung → /100
            | Jika bukan → bersihkan, lalu cek apakah perlu /100
            |
            */

            if (is_numeric($raw)) {
                $rxPowerMap[$ontIndex] = (float) $raw / 100;
                continue;
            }

            // Fallback: strip semua kecuali digit, titik, minus
            $cleaned = preg_replace('/[^0-9.\-]/', '', $raw);

            if ($cleaned === '' || !is_numeric($cleaned)) {
                continue;
            }

            $val = (float) $cleaned;

            // Jika |val| > 100, kemungkinan masih dalam satuan 0.01 dBm
            if (abs($val) > 100) {
                $val = $val / 100;
            }

            $rxPowerMap[$ontIndex] = $val;
        }

        return $rxPowerMap;
    }

    /*
    |--------------------------------------------------------------------------
    | DECODE ONU INDEX → PORT (alternative method)
    |--------------------------------------------------------------------------
    |
    | Metode alternatif untuk menentukan port dari SNMP index
    | tanpa bergantung pada ONU name format.
    |
    | Index encoding:
    |   onuIndex = PORT_INDEX_BASE + (portOffset * STEP) + onuId
    |   Contoh:  4718593 = 4718592 + (0 * 4096) + 1
    |            → PON01, ONU ID 1
    |
    */

    protected function decodeOnuIndexToPort(int $onuIndex): ?array
    {
        $offset = $onuIndex - self::PORT_INDEX_BASE;

        if ($offset < 0) {
            return null;
        }

        $portOffset = intdiv($offset, self::PORT_INDEX_STEP);
        $onuId      = $offset % self::PORT_INDEX_STEP;

        if ($portOffset < 0 || $portOffset >= self::PORT_COUNT) {
            return null;
        }

        $portNumber = $portOffset + 1;
        $portName   = 'PON' . str_pad($portNumber, 2, '0', STR_PAD_LEFT);

        return [
            'port_name'  => $portName,
            'port_index' => self::PORT_INDEX_BASE + ($portOffset * self::PORT_INDEX_STEP),
            'onu_id'     => $onuId,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GET ONTS
    |--------------------------------------------------------------------------
    */

    public function getOnts(string $portName): array
    {
        return array_values(
            array_filter(
                $this->getOnus(),
                fn($onu) => $onu['pon_port'] === $portName
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL (stub)
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | SERIAL NUMBER PARSER
    |--------------------------------------------------------------------------
    |
    | CData mengembalikan serial number dalam format hex bytes:
    |   "5A 54 45 47 CD 0B 33 A4" → "ZTEGCD0B33A4"
    |
    | 4 byte pertama = Vendor ID (ASCII)
    | 4 byte sisanya = Serial (hex)
    |
    | Jika sudah dalam format serial string (misal: "ZTEGCD0B33A4"),
    | kembalikan langsung.
    |
    */

    protected function parseSerialNumber(?string $raw): ?string
    {
        if (empty($raw)) {
            return null;
        }

        $raw = trim($raw);

        // Jika sudah format serial yang benar (4 huruf + 8 hex), return langsung
        if (preg_match('/^[A-Z]{4}[0-9A-F]{8,12}$/i', $raw)) {
            return strtoupper($raw);
        }

        // Coba parse hex byte format: "5A 54 45 47 CD 0B 33 A4" atau "5A544547CD0B33A4"
        preg_match_all('/[0-9A-Fa-f]{2}/', $raw, $matches);

        $bytes = $matches[0] ?? [];

        if (count($bytes) < 8) {
            // Tidak cukup hex bytes — kembalikan cleaned string
            $cleaned = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $raw));
            return $cleaned !== '' ? $cleaned : null;
        }

        // 4 byte pertama = vendor ID (ASCII)
        $vendor = '';
        for ($i = 0; $i < 4; $i++) {
            $char = @chr(hexdec($bytes[$i]));
            // Validasi printable ASCII
            if (!ctype_print($char)) {
                // Fallback: kembalikan raw yang dibersihkan
                return strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $raw)) ?: null;
            }
            $vendor .= $char;
        }

        // Sisa byte = serial dalam hex
        $serialHex = '';
        for ($i = 4; $i < count($bytes); $i++) {
            $serialHex .= strtoupper($bytes[$i]);
        }

        return strtoupper($vendor) . $serialHex;
    }
}
