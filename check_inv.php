<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Investor;
use App\Models\Transaction;

$inv = Investor::find(4);
if ($inv) {
    echo "Investor 4: " . $inv->name . " | Coordinator ID: " . $inv->coordinator_id . "\n";
} else {
    echo "Investor 4 not found.\n";
}

$t = Transaction::find(93);
echo "Transaction 93 Date: " . $t->transaction_date . "\n";
