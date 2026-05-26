<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Fixing Alpha Attendance ===" . PHP_EOL;

// Find attendance that have clock_in AND status = alpha
$wrongAttendances = \App\Models\TechnicianAttendance::where('status', 'alpha')
    ->whereNotNull('clock_in')
    ->get();

echo "Found " . $wrongAttendances->count() . " wrong attendance records!" . PHP_EOL;

foreach ($wrongAttendances as $att) {
    echo "Deleting ID " . $att->id . " (User: " . ($att->user ? $att->user->name : 'N/A') . ")" . PHP_EOL;
    $att->delete();
}

echo "Done!" . PHP_EOL;
