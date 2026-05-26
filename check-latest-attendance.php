<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Latest Attendance ===" . PHP_EOL;

$attendance = \App\Models\TechnicianAttendance::orderBy('id', 'desc')->first();
if ($attendance) {
    echo "ID: " . $attendance->id . PHP_EOL;
    echo "User: " . ($attendance->user ? $attendance->user->name : 'N/A') . PHP_EOL;
    echo "Work Date: " . ($attendance->work_date ? $attendance->work_date->toDateString() : 'NULL') . PHP_EOL;
    echo "Clock In: " . ($attendance->clock_in ? $attendance->clock_in->toDateTimeString() : 'NULL') . PHP_EOL;
    echo "Status: " . $attendance->status . PHP_EOL;
    echo "Notes: " . $attendance->notes . PHP_EOL;
    echo "Generated Type: " . $attendance->generated_type . PHP_EOL;
} else {
    echo "No attendance found!";
}
