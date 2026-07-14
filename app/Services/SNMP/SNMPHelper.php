<?php
// app/Services/SNMP/SNMPHelper.php

namespace App\Services\SNMP;

use Illuminate\Support\Facades\Log;

class SNMPHelper
{
    protected string $ip;
    protected string $readCommunity;
    protected ?string $writeCommunity;
    protected int $timeout;
    protected int $retries;

    public function __construct(
        string $ip,
        string $readCommunity = 'public',
        ?string $writeCommunity = null,
        int $timeout = 10,
        int $retries = 2
    ) {
        $this->ip = $ip;
        $this->readCommunity = $readCommunity;
        $this->writeCommunity = $writeCommunity;
        $this->timeout = $timeout;
        $this->retries = $retries;
    }

    public function get(string $oid): ?string
    {
        if ($this->hasNativeSnmp()) {
            $result = $this->nativeCall(fn () => snmp2_get(
                $this->ip,
                $this->readCommunity,
                $oid,
                $this->timeout * 1000000,
                $this->retries
            ));

            return is_string($result) ? $this->stripTypePrefix(trim($result)) : null;
        }

        $cmd = sprintf(
            'snmpget -v2c -c %s -t %d -r %d -Oqv %s %s 2>&1',
            escapeshellarg($this->readCommunity),
            $this->timeout,
            $this->retries,
            escapeshellarg($this->ip),
            escapeshellarg($oid)
        );

        $result = $this->executeShell($cmd);
        return $result ? $this->stripTypePrefix(trim($result)) : null;
    }

    public function stripTypePrefix(string $value): string
    {
        $prefixes = ['STRING:', 'INTEGER:', 'Hex-STRING:', 'Counter64:', 'Timeticks:', 'OID:', 'Counter32:', 'Gauge32:', 'IpAddress:', 'Opaque:'];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                $value = trim(substr($value, strlen($prefix)));
                break;
            }
        }
        // Strip surrounding quotes if present
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
        } elseif (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            $value = substr($value, 1, -1);
        }
        return trim($value);
    }

    public function walk(string $oid): array
    {
        if ($this->hasNativeSnmp()) {
            $result = $this->nativeCall(fn () => snmp2_real_walk(
                $this->ip,
                $this->readCommunity,
                $oid,
                $this->timeout * 1000000,
                $this->retries
            ));

            if (!is_array($result)) {
                return [];
            }

            $lines = [];
            foreach ($result as $walkOid => $value) {
                $numericOid = str_starts_with($walkOid, '.') ? $walkOid : '.' . $walkOid;
                $lines[] = $numericOid . ' = ' . trim((string) $value);
            }

            return $lines;
        }

        $cmd = sprintf(
            'snmpwalk -v2c -c %s -t %d -r %d -On %s %s 2>&1',
            escapeshellarg($this->readCommunity),
            $this->timeout,
            $this->retries,
            escapeshellarg($this->ip),
            escapeshellarg($oid)
        );
        $output = $this->executeShell($cmd);

        return $output ? explode("\n", trim($output)) : [];
    }

    public function walkValues(string $oid): array
    {
        if ($this->hasNativeSnmp()) {
            $result = $this->nativeCall(fn () => snmp2_walk(
                $this->ip,
                $this->readCommunity,
                $oid,
                $this->timeout * 1000000,
                $this->retries
            ));

            if (!is_array($result)) {
                return [];
            }

            return array_values(array_filter(array_map(
                static fn ($value) => trim((string) $value),
                $result
            )));
        }

        $cmd = sprintf(
            'snmpwalk -v2c -c %s -t %d -r %d -Oqv %s %s 2>&1',
            escapeshellarg($this->readCommunity),
            $this->timeout,
            $this->retries,
            escapeshellarg($this->ip),
            escapeshellarg($oid)
        );
        $output = $this->executeShell($cmd);

        return $output ? array_filter(explode("\n", trim($output))) : [];
    }

    public function set(string $oid, string $type, string $value): bool
    {
        if (!$this->writeCommunity) return false;

        if ($this->hasNativeSnmp()) {
            $result = $this->nativeCall(fn () => snmp2_set(
                $this->ip,
                $this->writeCommunity,
                $oid,
                $type,
                $value,
                $this->timeout * 1000000,
                $this->retries
            ));

            return $result !== false && $result !== null;
        }

        $cmd = sprintf(
            'snmpset -v2c -c %s -t %d -r %d %s %s %s %s 2>&1',
            escapeshellarg($this->writeCommunity),
            $this->timeout,
            $this->retries,
            escapeshellarg($this->ip),
            escapeshellarg($oid),
            escapeshellarg($type),
            escapeshellarg($value)
        );
        $output = $this->executeShell($cmd);

        return !empty($output);
    }

    public function hexToString(?string $hex): string
    {
        if (empty($hex)) return '';
        
        // Bersihkan string hex dari spasi
        $hex = preg_replace('/[^0-9A-Fa-f]/', '', $hex);
        
        if (strlen($hex) < 2) return $hex;
        
        $bytes = array_map('hexdec', str_split($hex, 2));
        $result = '';
        foreach ($bytes as $byte) {
            if ($byte >= 32 && $byte <= 126) {
                $result .= chr($byte);
            }
        }
        
        return trim($result);
    }

    public function hexToMac(?string $hex): string
    {
        if (empty($hex)) return '';
        $hex = preg_replace('/[^0-9A-Fa-f]/', '', $hex);
        if (strlen($hex) !== 12) return $hex;
        return implode(':', str_split(strtoupper($hex), 2));
    }

    public function parseTimeticks(?string $value): string
    {
        if (empty($value)) return '';
        preg_match('/\((\d+)\)/', $value, $m);
        if (!isset($m[1])) return $value;
        
        $ticks = (int)$m[1];
        $seconds = $ticks / 100;
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return "{$days}d {$hours}h {$minutes}m {$secs}s";
    }

    protected function hasNativeSnmp(): bool
    {
        return function_exists('snmp2_get')
            && function_exists('snmp2_walk')
            && function_exists('snmp2_real_walk')
            && function_exists('snmp2_set');
    }

    protected function nativeCall(callable $callback): mixed
    {
        if (function_exists('snmp_set_oid_output_format')) {
            snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
        }

        if (function_exists('snmp_set_quick_print')) {
            snmp_set_quick_print(true);
        }

        if (function_exists('snmp_set_valueretrieval')) {
            snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
        }

        $warning = null;
        set_error_handler(function (int $severity, string $message) use (&$warning) {
            $warning = $message;
            return true;
        });

        try {
            $result = $callback();
        } catch (\Throwable $e) {
            restore_error_handler();
            Log::error("SNMP exception for {$this->ip}: " . $e->getMessage());
            return null;
        }

        restore_error_handler();

        if ($warning) {
            Log::warning("SNMP warning from {$this->ip}: " . $warning);
        }

        if ($result === false || $result === null) {
            return null;
        }

        return $result;
    }

    protected function executeShell(string $cmd): ?string
    {
        try {
            $output = shell_exec($cmd);

            if ($output === null || $output === false) {
                return null;
            }

            $output = trim($output);

            if ($output === '' ||
                str_contains($output, 'Timeout') ||
                str_contains($output, 'No Response') ||
                str_contains($output, 'Error in packet') ||
                str_contains($output, 'is not recognized as an internal or external command')) {
                Log::warning("SNMP shell error from {$this->ip}: " . substr($output, 0, 200));
                return null;
            }

            return $output;
        } catch (\Throwable $e) {
            Log::error("SNMP shell exception for {$this->ip}: " . $e->getMessage());
            return null;
        }
    }
}
