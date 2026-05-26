<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Attendance Data ===" . PHP_EOL;
$attendances = \App\Models\TechnicianAttendance::orderBy('id', 'desc')->limit(20)->get();

foreach ($attendances as $a) {
    echo "ID: " . $a->id . 
         " | User: " . ($a->user ? $a->user->name : 'N/A') . 
         " | Work Date: " . ($a->work_date ? $a->work_date->toDateString() : 'NULL') . 
         " | Clock In: " . ($a->clock_in ? $a->clock_in->toDateTimeString() : 'NULL') . 
         " | Status: " . $a->status . PHP_EOL;
}
