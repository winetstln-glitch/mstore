<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Carbon\Carbon;

echo "=== Debug Clock In Window ===" . PHP_EOL;

$adminUser = User::where('name', 'Admin Mstore')->first();
if (!$adminUser) {
    echo "User not found!";
    exit;
}

$controller = new \App\Http\Controllers\TechnicianAttendanceController();

// Use reflection to call private methods
$reflection = new \ReflectionClass($controller);

// Call resolveTodayShiftInfo
$method1 = $reflection->getMethod('resolveTodayShiftInfo');
$method1->setAccessible(true);
$shiftInfo = $method1->invoke($controller, $adminUser);

echo "resolveTodayShiftInfo result:" . PHP_EOL;
var_dump($shiftInfo);

echo PHP_EOL;

// Call resolveClockInWindow
$method2 = $reflection->getMethod('resolveClockInWindow');
$method2->setAccessible(true);
$clockInWindow = $method2->invoke($controller, $adminUser);

echo "resolveClockInWindow result:" . PHP_EOL;
var_dump($clockInWindow);

echo PHP_EOL;

// Call determineClockInStatus
$method3 = $reflection->getMethod('determineClockInStatus');
$method3->setAccessible(true);
$now = Carbon::now();
$status = $method3->invoke($controller, $clockInWindow['official_start'], $clockInWindow['shift_cutoff'], $now);

echo "determineClockInStatus result at " . $now->toTimeString() . " = " . $status . PHP_EOL;
