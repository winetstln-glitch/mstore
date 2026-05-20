<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CREATE DASHBOARD PERMISSION ===\n";
echo str_repeat("=", 80) . "\n";

// Daftar permissions yang dibutuhkan
$requiredPermissions = [
    'dashboard.view',
    'olt.view',
    'olt.edit',
    'customer.view',
    'ticket.view',
    'inventory.view',
    'finance.view',
    'user.view',
    'user.edit',
    'role.view',
    'role.edit',
    'setting.view',
    'setting.edit',
];

// Step 1: Cari role admin
echo "\nStep 1: Cari role admin...\n";
$roleAdmin = DB::table('roles')->where('name', 'admin')->first();
if (!$roleAdmin) {
    echo "Role admin tidak ditemukan!\n";
    exit(1);
}
echo "Role admin ditemukan: ID {$roleAdmin->id}, Name {$roleAdmin->name}\n";

// Step 2: Buat permissions yang dibutuhkan
echo "\nStep 2: Buat permissions yang dibutuhkan...\n";
$count = 0;
foreach ($requiredPermissions as $permName) {
    $existing = DB::table('permissions')->where('name', $permName)->first();
    if (!$existing) {
        $permId = DB::table('permissions')->insertGetId([
            'name' => $permName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "  Created: {$permName} (ID: {$permId})\n";
        $count++;
        
        // Assign ke role admin
        DB::table('permission_role')->insert([
            'role_id' => $roleAdmin->id,
            'permission_id' => $permId,
        ]);
    } else {
        // Pastikan permission sudah diassign ke role admin
        $existingRolePerm = DB::table('permission_role')
            ->where('role_id', $roleAdmin->id)
            ->where('permission_id', $existing->id)
            ->first();
        if (!$existingRolePerm) {
            DB::table('permission_role')->insert([
                'role_id' => $roleAdmin->id,
                'permission_id' => $existing->id,
            ]);
            echo "  Assigned: {$permName}\n";
        }
    }
}

echo "\nBerhasil membuat {$count} permissions baru!\n";
echo "Total permissions untuk role admin: " . DB::table('permission_role')->where('role_id', $roleAdmin->id)->count() . "\n";

echo "\n=== SELESAI ===\n";
echo "Silakan refresh halaman dashboard!\n";
