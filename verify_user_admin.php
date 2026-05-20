<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== VERIFY USER ADMIN ===" . "\n";
echo str_repeat("=", 80) . "\n";

// Step 1: Cari user admin
echo "\nStep 1: Cari user admin..." . "\n";
$userAdmin = User::with('role')->where('email', 'admin@mstore.com')->first();
if (!$userAdmin) {
    echo "User admin tidak ditemukan!" . "\n";
    exit(1);
}
echo "User admin ditemukan!" . "\n";
echo "  ID: {$userAdmin->id}" . "\n";
echo "  Name: {$userAdmin->name}" . "\n";
echo "  Email: {$userAdmin->email}" . "\n";
echo "  Role ID: {$userAdmin->role_id}" . "\n";

// Step 2: Cek role
echo "\nStep 2: Cek role user admin..." . "\n";
if ($userAdmin->role) {
    echo "Role ditemukan!" . "\n";
    echo "  ID: {$userAdmin->role->id}" . "\n";
    echo "  Name: {$userAdmin->role->name}" . "\n";
    echo "  Label: {$userAdmin->role->label}" . "\n";
} else {
    echo "Role tidak ditemukan!" . "\n";
}

// Step 3: Cek hasRole('admin')
echo "\nStep 3: Cek hasRole('admin')..." . "\n";
$hasRoleAdmin = $userAdmin->hasRole('admin');
echo "hasRole('admin'): " . ($hasRoleAdmin ? 'YES' : 'NO') . "\n";

// Step 4: Cek hasPermission('dashboard.view')
echo "\nStep 4: Cek hasPermission('dashboard.view')..." . "\n";
$hasPermission = $userAdmin->hasPermission('dashboard.view');
echo "hasPermission('dashboard.view'): " . ($hasPermission ? 'YES' : 'NO') . "\n";

echo "\n=== SELESAI ===" . "\n";
