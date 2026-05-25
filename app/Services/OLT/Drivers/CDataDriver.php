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
    |
    | Field index (komponen ke-13 / index 12 pada 0-based):
    |   2   = name / description
    |   3   = serial number
    |   5   = vendor
    |   6   = model
    |   7   = status
    |   103 = last down cause
    |
    | Setelah field index, sisa komponen = ONU index
    | Bisa berupa:
    |   - Single integer: 4718593
    |   - Compound:       1.1.1 (slot.port.onuId)
    |
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
    | JUMLAH KOMPONEN BASE OID
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk ekstraksi index secara konsisten.
    |
    | .1.3.6.1.4.1.17409.2.8.4.1.1.{field}  → 13 komponen sebelum index
    | .1.3.6.1.4.1.17409.2.8.4.4.1.4        → 14 komponen sebelum index
    |
    */

    const ONU_TABLE_BASE_LENGTH = 13;  // komponen sebelum field+index
    const RX_POWER_BASE_LENGTH  = 14;  // komponen sebelum index

    /*
    |--------------------------------------------------------------------------
    | PORT CONFIG
    |--------------------------------------------------------------------------
    |
    | PORT_INDEX_BASE = 0x480000 = 4718592
    | PORT_INDEX_STEP = 0x1000  = 4096
    |
    */

    const PORT_COUNT      = 8;
    const PORT_INDEX_BASE = 4718592;
    const PORT_INDEX_STEP = 4096;

    /*
    |--------------------------------------------------------------------------
    | ONU STATUS MAP
    |--------------------------------------------------------------------------
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
    | Format: "gpon 0/0/X/Y"
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

        $this->snmp = new SNMPHelper(
            $ip,
            $readCommunity,
            $writeCommunity,
            3,
            0
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

        foreach (self::OID_SYS as $key => $oid) {
            try {
                $value = $this->snmp->get($oid);

                if (in_array($key, ['uptime', 'sys_uptime'], true)) {
                    $value = $this->snmp->parseTimeticks($value);
                }

                $info[$key] = $value;
            } catch (\Throwable $e) {
                Log::warning("CData getDeviceInfo [{$key}] failed: " . $e->getMessage());
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
    | GET ONUS (MAIN METHOD)
    |--------------------------------------------------------------------------
    |
    | Flow:
    |   1. Walk basic info (name, vendor, model, status)
    |   2. Walk serial number SEPARATELY
    |   3. Walk RX power SEPARATELY
    |   4. Merge semua by CONSISTENT ONU index
    |
    */

    public function getOnus(): array
    {
        $onus = [];

        try {
            $ports = $this->getPorts();

            /*
            |--------------------------------------------------------------
            | STEP 1: Walk basic ONU info
            |--------------------------------------------------------------
            */

            $ontData = $this->walkOnuBasicInfo();

            /*
            |--------------------------------------------------------------
            | STEP 2: Walk serial number SEPARATELY
            |--------------------------------------------------------------
            */

            $serialMap = $this->walkSerialNumbers();

            /*
            |--------------------------------------------------------------
            | STEP 3: Walk RX power SEPARATELY
            |--------------------------------------------------------------
            */

            $rxPowerMap = $this->walkRxPower();

            /*
            |--------------------------------------------------------------
            | STEP 4: Merge dan build ONU list
            |--------------------------------------------------------------
            */

            foreach ($ontData as $ontIndex => $data) {
                if (!isset($data['name'])) {
                    continue;
                }

                $ontName = $data['name'];

                // Parse "gpon 0/0/X/Y"
                if (!preg_match(self::ONU_NAME_PATTERN, $ontName, $matches)) {
                    Log::debug("CData ONU name skip (no pattern match): {$ontName}");
                    continue;
                }

                $portNumber = (int) $matches[1];
                $onuId      = (int) $matches[2];
                $portName   = 'PON' . str_pad($portNumber, 2, '0', STR_PAD_LEFT);

                if (!isset($ports[$portName])) {
                    Log::debug("CData ONU port not found: {$portName}");
                    continue;
                }

                $statusVal = $data['status_val'] ?? 2;
                $status    = self::ONU_STATUS_MAP[$statusVal] ?? 'unknown';

                // Ambil serial number: coba match by index
                $serialNumber = $serialMap[$ontIndex] ?? null;

                // Fallback: coba match by port-based index
                if ($serialNumber === null) {
                    $portBasedIndex = self::PORT_INDEX_BASE
                        + (($portNumber - 1) * self::PORT_INDEX_STEP)
                        + $onuId;
                    $serialNumber = $serialMap[$portBasedIndex] ?? null;
                }

                // Fallback: coba dari data basic walk
                if ($serialNumber === null) {
                    $serialNumber = $data['serial_number'] ?? null;
                }

                $onus[] = [
                    'interface'       => "{$portName}/{$onuId}",
                    'ont_id'          => (string) $onuId,
                    'name'            => $ontName,
                    'vendor'          => $data['vendor']          ?? null,
                    'model'           => $data['model']           ?? null,
                    'serial_number'   => $serialNumber,
                    'mac_address'     => null,
                    'rx_power'        => $rxPowerMap[$ontIndex]   ?? null,
                    'tx_power'        => null,
                    'voltage'         => null,
                    'temperature'     => null,
                    'status'          => $status,
                    'pon_port'        => $portName,
                    'last_down_cause' => $data['last_down_cause'] ?? null,
                ];
            }

            Log::info('CData ONU parsed', [
                'count'     => count($onus),
                'serial_ok' => count(array_filter($onus, fn($o) => $o['serial_number'] !== null)),
                'serial_map_keys' => array_slice(array_keys($serialMap), 0, 5),
                'ont_data_keys'   => array_slice(array_keys($ontData), 0, 5),
            ]);

        } catch (\Throwable $e) {
            Log::error('CData getOnus failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $onus;
    }

    /*
    |--------------------------------------------------------------------------
    | WALK ONU BASIC INFO
    |--------------------------------------------------------------------------
    |
    | Walk .1.3.6.1.4.1.17409.2.8.4.1.1
    | Ambil: name(2), serial(3), vendor(5), model(6), status(7), last_down(103)
    |
    | Index = SEMUA komponen setelah field number
    |   Contoh: ...1.1.2.1.1.3  →  field=2, index="1.1.3"
    |   Contoh: ...1.1.2.4718593 →  field=2, index="4718593"
    |
    */

    protected function walkOnuBasicInfo(): array
    {
        $ontData = [];

        try {
            $allRaw = $this->snmp->walk('.1.3.6.1.4.1.17409.2.8.4.1.1');
        } catch (\Throwable $e) {
            Log::error('CData walkOnuBasicInfo failed: ' . $e->getMessage());
            return $ontData;
        }

        foreach ($allRaw as $line) {
            $parts = explode(' = ', $line, 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$oidPart, $valuePart] = $parts;

            $oidComponents = explode('.', ltrim($oidPart, '.'));

            /*
            |----------------------------------------------------------
            | FIX: Ekstrak index secara konsisten
            |----------------------------------------------------------
            |
            | Komponen 0-11 = base OID prefix
            | Komponen 12   = field number (2,3,5,6,7,103)
            | Komponen 13+  = ONU index (bisa 1 atau lebih komponen)
            |
            | LAMA: $ontIndex = $oidComponents[13]
            |       → Hanya ambil 1 komponen → compound index RUSAK
            |
            | BARU: implode semua sisa komponen → index KONSISTEN
            |
            */

            if (count($oidComponents) < 14) {
                continue;
            }

            $fieldIdx = (int) $oidComponents[12];

            // FIX: Ambil SEMUA komponen setelah field sebagai index
            $ontIndex = implode('.', array_slice($oidComponents, 13));

            $value = trim($this->snmp->stripTypePrefix($valuePart), '"');

            if (!isset($ontData[$ontIndex])) {
                $ontData[$ontIndex] = [];
            }

            switch ($fieldIdx) {
                case 2:   // Name
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

                case 103: // Last Down Cause
                    $ontData[$ontIndex]['last_down_cause'] = $value;
                    break;
            }
        }

        return $ontData;
    }

    /*
    |--------------------------------------------------------------------------
    | WALK SERIAL NUMBERS (SEPARATE)
    |--------------------------------------------------------------------------
    |
    | Walk OID serial number secara terpisah untuk reliability.
    | Menggunakan index yang SAMA dengan walkOnuBasicInfo().
    |
    | Format index hasil walk:
    |   ...1.3.{index}  →  index mulai dari komponen ke-13
    |
    */

    protected function walkSerialNumbers(): array
    {
        $serialMap = [];

        try {
            $rawLines = $this->snmp->walk(self::OID_ONT['serial_number']);
        } catch (\Throwable $e) {
            Log::warning('CData walkSerialNumbers failed: ' . $e->getMessage());
            return $serialMap;
        }

        foreach ($rawLines as $line) {
            $parts = explode(' = ', $line, 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$oidPart, $valuePart] = $parts;

            $oidComponents = explode('.', ltrim($oidPart, '.'));

            if (count($oidComponents) < 14) {
                continue;
            }

            // Index dimulai dari komponen ke-13 (setelah field number "3")
            $ontIndex = implode('.', array_slice($oidComponents, 13));

            $raw = trim($this->snmp->stripTypePrefix($valuePart), '"');

            $serial = $this->parseSerialNumber($raw);

            if ($serial !== null) {
                $serialMap[$ontIndex] = $serial;
            }

            // Debug: log 3 sampel pertama
            if (count($serialMap) <= 3) {
                Log::debug("CData serial sample", [
                    'ontIndex'    => $ontIndex,
                    'raw_value'   => $raw,
                    'raw_bytes'   => bin2hex(substr($raw, 0, 20)),
                    'parsed'      => $serial,
                ]);
            }
        }

        Log::info('CData serial map built', [
            'count'    => count($serialMap),
            'samples'  => array_slice($serialMap, 0, 3, true),
        ]);

        return $serialMap;
    }

    /*
    |--------------------------------------------------------------------------
    | WALK RX POWER (SEPARATE)
    |--------------------------------------------------------------------------
    |
    | Walk OID rx_power secara terpisah.
    |
    | OID: .1.3.6.1.4.1.17409.2.8.4.4.1.4.{index}[.0.0]
    |
    | Index dimulai dari komponen ke-14.
    | Trailing .0.0 mungkin ada dan perlu di-strip untuk matching.
    |
    | CData mengembalikan Integer32 dalam satuan 0.01 dBm.
    | Contoh: -1850 → -18.50 dBm
    |
    */

    protected function walkRxPower(): array
    {
        $rxPowerMap = [];

        try {
            $rawLines = $this->snmp->walk(self::OID_ONT['rx_power']);
        } catch (\Throwable $e) {
            Log::warning('CData walkRxPower failed: ' . $e->getMessage());
            return $rxPowerMap;
        }

        foreach ($rawLines as $line) {
            $parts = explode(' = ', $line, 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$oidPart, $valuePart] = $parts;

            $oidComponents = explode('.', ltrim($oidPart, '.'));

            if (count($oidComponents) < self::RX_POWER_BASE_LENGTH + 1) {
                continue;
            }

            /*
            |----------------------------------------------------------
            | FIX: Ekstrak index secara konsisten
            |----------------------------------------------------------
            |
            | Base OID: .1.3.6.1.4.1.17409.2.8.4.4.1.4  (14 komponen)
            | Index:    komponen ke-14 dan seterusnya
            |
            | Contoh: ...4.1.4.4718593.0.0
            |          → fullIndex = "4718593.0.0"
            |          → ontIndex  = "4718593" (strip .0.0)
            |
            | Contoh: ...4.1.4.1.1.3.0.0
            |          → fullIndex = "1.1.3.0.0"
            |          → ontIndex  = "1.1.3" (strip .0.0)
            |
            */

            $fullIndex = implode('.', array_slice($oidComponents, self::RX_POWER_BASE_LENGTH));

            // Strip trailing .0.0 (channel/direction suffix)
            $ontIndex = preg_replace('/(\.0)+$/', '', $fullIndex);

            $raw = trim($this->snmp->stripTypePrefix($valuePart));

            /*
            |----------------------------------------------------------
            | Parse RX Power value
            |----------------------------------------------------------
            |
            | Format umum CData: Integer32 dalam 0.01 dBm
            |   -1850 → -18.50 dBm
            |
            | Tapi bisa juga langsung dalam dBm:
            |   -18.50 → -18.50 dBm
            |
            */

            if (is_numeric($raw)) {
                $val = (float) $raw;

                // Jika |val| > 100, kemungkinan satuan 0.01 dBm
                $rxPowerMap[$ontIndex] = abs($val) > 100
                    ? $val / 100
                    : $val;

                continue;
            }

            // Fallback: bersihkan non-numeric kecuali titik dan minus
            $cleaned = preg_replace('/[^0-9.\-]/', '', $raw);

            if ($cleaned !== '' && is_numeric($cleaned)) {
                $val = (float) $cleaned;
                $rxPowerMap[$ontIndex] = abs($val) > 100
                    ? $val / 100
                    : $val;
            }
        }

        return $rxPowerMap;
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
    | SERIAL NUMBER PARSER (COMPREHENSIVE)
    |--------------------------------------------------------------------------
    |
    | CData bisa mengembalikan serial number dalam berbagai format:
    |
    | Format 1 — Hex-STRING (paling umum):
    |   "5A 54 45 47 CD 0B 33 A4"  →  "ZTEGCD0B33A4"
    |   4 byte pertama = Vendor ID (ASCII)
    |   4 byte sisanya = Serial (hex uppercase)
    |
    | Format 2 — String langsung:
    |   "ZTEGCD0B33A4"              →  "ZTEGCD0B33A4"
    |
    | Format 3 — Continuous hex:
    |   "5A544547CD0B33A4"          →  "ZTEGCD0B33A4"
    |
    | Format 4 — Raw binary (OCTET STRING):
    |   Byte: 0x5A 0x54 0x45 0x47 0xCD 0x0B 0x33 0xA4
    |   →  "ZTEGCD0B33A4"
    |
    | Format 5 — Hex dengan prefix yang tidak ter-strip:
    |   "Hex-STRING: 5A 54 45 47 CD 0B 33 A4"
    |   →  "ZTEGCD0B33A4"
    |
    */

    protected function parseSerialNumber(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $raw = trim($raw);

        /*
        |----------------------------------------------------------
        | Step 1: Cek apakah sudah format serial yang benar
        |----------------------------------------------------------
        |
        | GPON serial format: 4 huruf vendor + 8+ hex digits
        | Contoh: ZTEGCD0B33A4, HWTC12345678, FHTT01234567
        |
        */

        if (preg_match('/^[A-Za-z]{4}[0-9A-Fa-f]{8,12}$/', $raw)) {
            return strtoupper($raw);
        }

        /*
        |----------------------------------------------------------
        | Step 2: Coba parse sebagai hex bytes
        |----------------------------------------------------------
        |
        | Deteksi pasangan hex: "5A 54 45 47 CD 0B 33 A4"
        | Atau continuous hex:  "5A544547CD0B33A4"
        | Atau dengan prefix:   "Hex-STRING: 5A 54 45 47 CD 0B 33 A4"
        |
        | preg_match_all menemukan SEMUA pasangan hex 2 digit
        | bahkan jika ada teks lain di antaranya.
        |
        */

        preg_match_all('/[0-9A-Fa-f]{2}/', $raw, $matches);
        $hexBytes = $matches[0] ?? [];

        if (count($hexBytes) >= 8) {
            return $this->buildSerialFromHexBytes($hexBytes);
        }

        /*
        |----------------------------------------------------------
        | Step 3: Coba parse sebagai raw binary string
        |----------------------------------------------------------
        |
        | Jika value berisi byte non-printable, itu kemungkinan
        | raw OCTET STRING dari SNMP.
        |
        | Contoh: chr(0x5A).chr(0x54).chr(0x45).chr(0x47)...
        |
        */

        if ($this->containsNonPrintable($raw)) {
            $hexBytes = $this->binaryToHexBytes($raw);

            if (count($hexBytes) >= 8) {
                return $this->buildSerialFromHexBytes($hexBytes);
            }
        }

        /*
        |----------------------------------------------------------
        | Step 4: Fallback — bersihkan dan return
        |----------------------------------------------------------
        |
        | Hapus semua karakter non-alfanumerik, return uppercase.
        | Jika hasilnya kosong, return null.
        |
        */

        $cleaned = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $raw));

        return $cleaned !== '' ? $cleaned : null;
    }

    /**
     * Build serial number dari array hex byte strings.
     *
     * 4 byte pertama = Vendor ID (konversi ke ASCII)
     * Sisa byte      = Serial number (hex uppercase)
     *
     * @param  string[]  $hexBytes  ["5A", "54", "45", "47", "CD", "0B", "33", "A4"]
     * @return string|null           "ZTEGCD0B33A4"
     */
    protected function buildSerialFromHexBytes(array $hexBytes): ?string
    {
        // 4 byte pertama = Vendor ID
        $vendor = '';
        for ($i = 0; $i < 4; $i++) {
            $char = @chr(hexdec($hexBytes[$i]));

            // Validasi: harus printable ASCII
            if ($char === false || !ctype_print($char)) {
                // Vendor ID tidak valid — kemungkinan ini bukan serial number
                // Fallback: return semua byte sebagai hex string
                return strtoupper(implode('', $hexBytes));
            }

            $vendor .= $char;
        }

        // Sisa byte = Serial dalam hex
        $serialHex = '';
        $byteCount = count($hexBytes);
        for ($i = 4; $i < $byteCount; $i++) {
            $serialHex .= strtoupper($hexBytes[$i]);
        }

        return strtoupper($vendor) . $serialHex;
    }

    /**
     * Cek apakah string mengandung karakter non-printable (indikasi raw binary).
     */
    protected function containsNonPrintable(string $str): bool
    {
        return preg_match('/[^\x20-\x7E\t\r\n]/', $str) === 1;
    }

    /**
     * Konversi raw binary string ke array hex byte strings.
     *
     * @return string[]  ["5A", "54", "45", ...]
     */
    protected function binaryToHexBytes(string $binary): array
    {
        $hexBytes = [];
        $len = strlen($binary);

        for ($i = 0; $i < $len; $i++) {
            $hexBytes[] = strtoupper(sprintf('%02X', ord($binary[$i])));
        }

        return $hexBytes;
    }
}