<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== CREATE DEFAULT USER & OLT ===\n";
echo str_repeat("=", 80) . "\n";

// Step 1: Create default admin user
echo "\nStep 1: Membuat user admin default...\n";
$user = User::where('email', 'admin@mstore.com')->first();
if (!$user) {
    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@mstore.com',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
    echo "User admin berhasil dibuat!\n";
    echo "Email: admin@mstore.com\n";
    echo "Password: password\n";
} else {
    echo "User admin sudah ada!\n";
    echo "Email: admin@mstore.com\n";
    echo "Password: password (default)\n";
}

// Step 2: Tambahkan OLT HSGQ-CISARAP kembali (menggunakan DB facade langsung)
echo "\nStep 2: Menambahkan OLT-HSGQ-CISARAP...\n";
$olt = DB::table('olts')->where('host', '192.168.80.3')->first();
if (!$olt) {
    $oltId = DB::table('olts')->insertGetId([
        'name' => 'OLT-HSGQ-CISARAP',
        'host' => '192.168.80.3',
        'port' => 23,
        'username' => 'root',
        'is_active' => true,
        'type' => 'gpon',
        'brand' => 'hsgq',
        'snmp_version' => '2c',
        'snmp_port' => 161,
        'snmp_community' => 'MstoreRead2026',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "OLT-HSGQ-CISARAP berhasil ditambahkan! ID: {$oltId}\n";
} else {
    echo "OLT-HSGQ-CISARAP sudah ada! ID: {$olt->id}\n";
    $oltId = $olt->id;
}

echo "\n=== SELESAI ===\n";
echo "Silakan login dengan:\n";
echo "Email: admin@mstore.com\n";
echo "Password: password\n";
echo "\nSetelah login, buka /olt/{$oltId} dan klik 'Sync OLT Data' untuk sync ONU!\n";
