<?php

namespace App\Services;

class SystemMetricsService
{
    public function getMetrics(): array
    {
        $cores = $this->getCoreCount();
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $cpuUsagePct = $cores > 0 ? min(100, round(($loadAvg[0] / $cores) * 100)) : 0;

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
