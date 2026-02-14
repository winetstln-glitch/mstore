<?php

namespace App\Services;

class SystemMetricsService
{
    public function getMetrics(): array
    {
        $cores = $this->getCoreCount();
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $cpuUsagePct = $this->getCpuUsagePct($cores, $loadAvg);

        $memInfo = $this->parseMemInfo();
        $memTotalKb = $memInfo['MemTotal'] ?? 0;
        $memAvailKb = $memInfo['MemAvailable'] ?? ($memInfo['MemFree'] ?? 0);
        $swapTotalKb = $memInfo['SwapTotal'] ?? 0;
        $swapFreeKb = $memInfo['SwapFree'] ?? 0;

        $ramUsedKb = max(0, $memTotalKb - $memAvailKb);
        $swapUsedKb = max(0, $swapTotalKb - $swapFreeKb);

        $ramTotalMb = round($memTotalKb / 1024, 2);
        $ramUsedMb = round($ramUsedKb / 1024, 2);
        $ramFreeMb = round($memAvailKb / 1024, 2);

        $swapTotalMb = round($swapTotalKb / 1024, 2);
        $swapUsedMb = round($swapUsedKb / 1024, 2);
        $swapFreeMb = round($swapFreeKb / 1024, 2);

        $ramUsedPct = $ramTotalMb > 0 ? round(($ramUsedMb / $ramTotalMb) * 100) : 0;
        $swapUsedPct = $swapTotalMb > 0 ? round(($swapUsedMb / $swapTotalMb) * 100) : 0;

        return [
            'cpu_usage_pct' => $cpuUsagePct,
            'ram_total_mb' => $ramTotalMb,
            'ram_used_mb' => $ramUsedMb,
            'ram_free_mb' => $ramFreeMb,
            'ram_used_pct' => $ramUsedPct,
            'swap_total_mb' => $swapTotalMb,
            'swap_used_mb' => $swapUsedMb,
            'swap_free_mb' => $swapFreeMb,
            'swap_used_pct' => $swapUsedPct,
            'loadavg' => $loadAvg,
            'cores' => $cores,
        ];
    }

    private function getCpuUsagePct(int $cores, array $loadAvg): int
    {
        if (is_readable('/proc/stat')) {
            $a = $this->readProcStat();
            usleep(200000);
            $b = $this->readProcStat();
            if ($a && $b) {
                $idleA = $a['idle'] + $a['iowait'];
                $idleB = $b['idle'] + $b['iowait'];
                $nonIdleA = $a['user'] + $a['nice'] + $a['system'] + $a['irq'] + $a['softirq'] + $a['steal'];
                $nonIdleB = $b['user'] + $b['nice'] + $b['system'] + $b['irq'] + $b['softirq'] + $b['steal'];
                $totalA = $idleA + $nonIdleA;
                $totalB = $idleB + $nonIdleB;
                $totalDelta = max(1, $totalB - $totalA);
                $idleDelta = max(0, $idleB - $idleA);
                $pct = (int)round((1 - ($idleDelta / $totalDelta)) * 100);
                if ($pct < 0) $pct = 0;
                if ($pct > 100) $pct = 100;
                return $pct;
            }
        }
        return $cores > 0 ? min(100, (int)round(($loadAvg[0] / $cores) * 100)) : 0;
    }

    private function readProcStat(): array
    {
        $line = '';
        $fh = @fopen('/proc/stat', 'r');
        if ($fh) {
            $line = fgets($fh);
            fclose($fh);
        }
        if (!$line || strpos($line, 'cpu ') !== 0) {
            return [];
        }
        $parts = preg_split('/\s+/', trim($line));
        $vals = array_slice($parts, 1);
        $vals = array_map('intval', $vals);
        return [
            'user' => $vals[0] ?? 0,
            'nice' => $vals[1] ?? 0,
            'system' => $vals[2] ?? 0,
            'idle' => $vals[3] ?? 0,
            'iowait' => $vals[4] ?? 0,
            'irq' => $vals[5] ?? 0,
            'softirq' => $vals[6] ?? 0,
            'steal' => $vals[7] ?? 0,
        ];
    }

    private function getCoreCount(): int
    {
        // Linux: parse /proc/cpuinfo
        if (is_readable('/proc/cpuinfo')) {
            $data = @file_get_contents('/proc/cpuinfo') ?: '';
            if ($data) {
                return preg_match_all('/^processor\\s*:\\s*\\d+/m', $data);
            }
        }
        // Fallback: 1 core
        return 1;
    }

    private function parseMemInfo(): array
    {
        $result = [];
        if (is_readable('/proc/meminfo')) {
            $lines = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                if (preg_match('/^(\\w+):\\s+(\\d+)\\s*kB$/', $line, $m)) {
                    $result[$m[1]] = (int)$m[2];
                }
            }
        }
        return $result;
    }
}
