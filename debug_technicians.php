<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing technicians query ===\n";
use App\Models\User;

$users = User::whereHas('role', function ($q) {
    $q->where('name', '!=', 'customer')
      ->where('name', '!=', 'koordinator')
      ->where('name', '!=', 'coordinator');
})->where('is_active', true)->with('role')->orderBy('name')->get();

echo "Count: " . $users->count() . "\n";
foreach($users as $user) {
    echo "- ID: " . $user->id . ", Name: " . $user->name . ", Role: " . ($user->role->name ?? 'N/A') . "\n";
}
