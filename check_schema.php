<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = ['wash_transactions', 'wash_transaction_items', 'atk_transactions', 'atk_transaction_items', 'atk_products'];
foreach ($tables as $table) {
    echo "Table: $table\n";
    $cols = Illuminate\Support\Facades\Schema::getColumnListing($table);
    print_r($cols);
    echo "\n";
}
