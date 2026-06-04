<?php

if (! function_exists('format_duration')) {
    function format_duration($seconds)
    {
        if (! $seconds || $seconds <= 0) {
            return '-';
        }

        $seconds = (int) $seconds;
        
        // Define time units in seconds
        $units = [
            'bulan' => 2592000, // 30 days
            'hari' => 86400,
            'jam' => 3600,
            'menit' => 60,
            'detik' => 1
        ];

        foreach ($units as $unit => $value) {
            if ($seconds >= $value) {
                $count = floor($seconds / $value);
                return $count . ' ' . $unit;
            }
        }

        return $seconds . ' detik';
    }
}
