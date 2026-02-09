<?php
// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$columns = Schema::getColumnListing('wash_transaction_items');
echo "Columns in wash_transaction_items:\n";
print_r($columns);

echo "\nDetailed Info:\n";
$info = DB::select("PRAGMA table_info(wash_transaction_items)");
print_r($info);
