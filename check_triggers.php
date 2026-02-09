<?php
// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$triggers = DB::select("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = 'wash_transactions'");
print_r($triggers);
