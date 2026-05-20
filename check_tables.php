<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECK TABLE STRUCTURE ===\n";
echo str_repeat("=", 80) . "\n";

// Step 1: List semua tabel
echo "\nStep 1: List semua tabel di database...\n";
$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
foreach ($tables as $table) {
    echo "  - {$table->name}\n";
}

// Step 2: Cek struktur tabel roles
echo "\nStep 2: Struktur tabel roles...\n";
$columns = DB::select("PRAGMA table_info(roles)");
foreach ($columns as $col) {
    echo "  - {$col->name} ({$col->type})\n";
}

// Step 3: Cek struktur tabel users
echo "\nStep 3: Struktur tabel users...\n";
$columns = DB::select("PRAGMA table_info(users)");
foreach ($columns as $col) {
    echo "  - {$col->name} ({$col->type})\n";
}

echo "\n=== SELESAI ===\n";
