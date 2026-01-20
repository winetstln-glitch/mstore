<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;

$count = Transaction::where('category', 'Investor Cash Fund')->count();
echo "Total Investor Cash Fund transactions: " . $count . "\n";

$nullCount = Transaction::where('category', 'Investor Cash Fund')->whereNull('investor_id')->count();
echo "With NULL investor_id: " . $nullCount . "\n";

$all = Transaction::where('category', 'Investor Cash Fund')->get();
foreach($all as $t) {
    echo "ID: " . $t->id . " | InvID: " . $t->investor_id . " | Amount: " . $t->amount . "\n";
}
