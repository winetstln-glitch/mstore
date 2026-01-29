<?php

namespace App\Helpers;

class FormatHelper
{
    public static function bytes(int|string|null $bytes, int $precision = 2): string
    {
        if ($bytes === null || $bytes === '' || !is_numeric($bytes)) {
            return '0 B';
        }

        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $pow = (int) floor(log($bytes, 1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1024 ** $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

