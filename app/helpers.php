<?php

if (! function_exists('format_duration')) {
    function format_duration($seconds)
    {
        if (! $seconds || $seconds <= 0) {
            return '-';
        }

        $seconds = (int) $seconds;

        $units = [
            'bulan' => 2592000,
            'hari'  => 86400,
            'jam'   => 3600,
            'menit' => 60,
            'detik' => 1,
        ];

        $parts = [];
        $remainder = $seconds;

        foreach ($units as $unit => $value) {
            if ($remainder >= $value) {
                $count = (int) floor($remainder / $value);
                $remainder = $remainder % $value;
                if ($count > 0) {
                    $parts[] = $count . ' ' . $unit;
                }
            }
            if ($remainder <= 0) {
                break;
            }
        }

        if (count($parts) === 0) {
            return $seconds . ' detik';
        }

        return implode(' ', $parts);
    }
}
