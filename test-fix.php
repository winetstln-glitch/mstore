<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Carbon\Carbon;

echo "=== Delete Attendance 20 ===" . PHP_EOL;
$att20 = \App\Models\TechnicianAttendance::find(20);
if ($att20) {
    $att20->delete();
    echo "Deleted attendance 20!" . PHP_EOL;
}

echo PHP_EOL;
echo "=== Test New Shift Cutoff ===" . PHP_EOL;

$adminUser = User::where('name', 'Admin Mstore')->first();
$controller = new \App\Http\Controllers\TechnicianAttendanceController();

$reflection = new \ReflectionClass($controller);

// Call resolveClockInWindow
$method2 = $reflection->getMethod('resolveClockInWindow');
$method2->setAccessible(true);
$clockInWindow = $method2->invoke($controller, $adminUser);

echo "New shift_cutoff: " . $clockInWindow['shift_cutoff'] . PHP_EOL;

// Call determineClockInStatus
$method3 = $reflection->getMethod('determineClockInStatus');
$method3->setAccessible(true);
$now = Carbon::now();
$status = $method3->invoke($controller, $clockInWindow['official_start'], $clockInWindow['shift_cutoff'], $now);

echo "Current time: " . $now->toTimeString() . PHP_EOL;
echo "determineClockInStatus result: " . $status . PHP_EOL;
