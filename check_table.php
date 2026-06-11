<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Struktur kasbon_loans ===\n";
$columnsLoans = DB::select("PRAGMA table_info(kasbon_loans)");
foreach ($columnsLoans as $col) {
    echo "- {$col->name} ({$col->type})\n";
}

echo "\n=== Struktur kasbon_installments ===\n";
$columnsInstallments = DB::select("PRAGMA table_info(kasbon_installments)");
foreach ($columnsInstallments as $col) {
    echo "- {$col->name} ({$col->type})\n";
}
