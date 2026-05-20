<?php

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        if ($bytes === null || (is_numeric($bytes) && $bytes == 0)) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max((float) $bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('formatUptime')) {
    function formatUptime($seconds) {
        if ($seconds === null || !is_numeric($seconds)) {
            return 'N/A';
        }
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        $parts[] = "{$secs}s";
        
        return implode(' ', $parts);
    }
}

if (!function_exists('snmpErrorToString')) {
    function snmpErrorToString($errorCode) {
        $errors = [
            -1 => 'General SNMP error',
            0 => 'Success',
            1 => 'Too big',
            2 => 'No such name',
            3 => 'Bad value',
            4 => 'Read only',
            5 => 'Generic error',
            6 => 'No access',
            7 => 'Wrong type',
            8 => 'Wrong length',
            9 => 'Wrong encoding',
            10 => 'Wrong value',
            11 => 'No creation',
            12 => 'Inconsistent value',
            13 => 'Resource unavailable',
            14 => 'Commit failed',
            15 => 'Undo failed',
            16 => 'Authorization error',
            17 => 'Not writable',
            18 => 'Inconsistent name',
        ];
        return $errors[$errorCode] ?? "Unknown error ({$errorCode})";
    }
}