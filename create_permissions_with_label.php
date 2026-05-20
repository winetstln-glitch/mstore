<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CREATE PERMISSIONS WITH LABEL ===\n";
echo str_repeat("=", 80) . "\n";

// Daftar permissions yang dibutuhkan
$permissionsToCreate = [
    ['name' => 'dashboard.view', 'label' => 'View Dashboard', 'group' => 'Dashboard'],
    ['name' => 'olt.view', 'label' => 'View OLT', 'group' => 'OLT'],
    ['name' => 'olt.edit', 'label' => 'Edit OLT', 'group' => 'OLT'],
    ['name' => 'customer.view', 'label' => 'View Customer', 'group' => 'Customer'],
    ['name' => 'ticket.view', 'label' => 'View Ticket', 'group' => 'Ticket'],
    ['name' => 'inventory.view', 'label' => 'View Inventory', 'group' => 'Inventory'],
    ['name' => 'finance.view', 'label' => 'View Finance', 'group' => 'Finance'],
    ['name' => 'user.view', 'label' => 'View User', 'group' => 'User'],
    ['name' => 'user.edit', 'label' => 'Edit User', 'group' => 'User'],
    ['name' => 'role.view', 'label' => 'View Role', 'group' => 'Role'],
    ['name' => 'role.edit', 'label' => 'Edit Role', 'group' => 'Role'],
    ['name' => 'setting.view', 'label' => 'View Setting', 'group' => 'Setting'],
    ['name' => 'setting.edit', 'label' => 'Edit Setting', 'group' => 'Setting'],
];

// Step 1: Cari role admin
echo "\nStep 1: Cari role admin...\n";
$roleAdmin = DB::table('roles')->where('name', 'admin')->first();
if (!$roleAdmin) {
    echo "Role admin tidak ditemukan!\n";
    exit(1);
}
echo "Role admin ditemukan: ID {$roleAdmin->id}, Name {$roleAdmin->name}\n";

// Step 2: Buat dan assign permissions
echo "\nStep 2: Buat dan assign permissions...\n";
$count = 0;
foreach ($permissionsToCreate as $permData) {
    $existing = DB::table('permissions')->where('name', $permData['name'])->first();
    if (!$existing) {
        $permId = DB::table('permissions')->insertGetId([
            'name' => $permData['name'],
            'label' => $permData['label'],
            'group' => $permData['group'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "  Created: {$permData['name']} (ID: {$permId})\n";
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
            echo "  Assigned: {$permData['name']}\n";
        }
    }
}

echo "\nBerhasil membuat {$count} permissions baru!\n";
echo "Total permissions untuk role admin: " . DB::table('permission_role')->where('role_id', $roleAdmin->id)->count() . "\n";

echo "\n=== SELESAI ===\n";
echo "Silakan refresh halaman dashboard!\n";
