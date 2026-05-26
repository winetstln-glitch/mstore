<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== LATEST ATTENDANCE ===" . PHP_EOL;
$admin = \App\Models\User::where('name', 'Admin Mstore')->first();

$attendances = \App\Models\TechnicianAttendance::where('user_id', $admin->id)
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

foreach ($attendances as $att) {
    echo "ID: " . $att->id . PHP_EOL;
    echo "Work Date: " . ($att->work_date ? $att->work_date->toDateString() : 'NULL') . PHP_EOL;
    echo "Clock In: " . ($att->clock_in ? $att->clock_in->toDateTimeString() : 'NULL') . PHP_EOL;
    echo "Clock Out: " . ($att->clock_out ? $att->clock_out->toDateTimeString() : 'NULL') . PHP_EOL;
    echo "Status: " . $att->status . PHP_EOL;
    echo "Generated Type: " . $att->generated_type . PHP_EOL;
    echo "---" . PHP_EOL;
}
