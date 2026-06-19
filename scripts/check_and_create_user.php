<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== CHECK & CREATE USER ADMIN ===\n";
echo str_repeat("=", 80) . "\n";

// Step 1: Cek semua user di database
echo "\nStep 1: Cek semua user di database...\n";
$users = DB::table('users')->get();
if ($users->isEmpty()) {
    echo "Tidak ada user di database!\n";
} else {
    echo "Ditemukan " . count($users) . " user:\n";
    foreach ($users as $user) {
        echo "  ID: {$user->id}, Email: {$user->email}, Name: {$user->name}\n";
    }
}

// Step 2: Buat user admin baru
echo "\nStep 2: Membuat user admin...\n";
$userAdmin = User::where('email', 'admin@mstore.com')->first();
if (!$userAdmin) {
    $userAdmin = User::create([
        'name' => 'Admin Mstore',
        'email' => 'admin@mstore.com',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
    echo "User admin berhasil dibuat!\n";
    echo "ID: {$userAdmin->id}\n";
    echo "Email: {$userAdmin->email}\n";
    echo "Password: password\n";
} else {
    echo "User admin sudah ada!\n";
    echo "ID: {$userAdmin->id}\n";
    echo "Email: {$userAdmin->email}\n";
    echo "Password: password (default)\n";
}

echo "\n=== SELESAI ===\n";
echo "Silakan login dengan:\n";
echo "Email: admin@mstore.com\n";
echo "Password: password\n";
