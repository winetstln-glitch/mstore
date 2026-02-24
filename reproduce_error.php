<?php

use App\Models\Asset;
use App\Models\Coordinator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Pick a user who likely has assets or at least exists
$targetUser = User::whereHas('role', function ($q) {
    $q->where('name', '!=', 'admin');
})->first();

if (! $targetUser) {
    $targetUser = User::first();
}

echo 'Testing with user: '.$targetUser->name.' (ID: '.$targetUser->id.")\n";

// Mock Auth as Admin to bypass permission checks in controller logic simulation
$admin = User::whereHas('role', function ($q) {
    $q->where('name', 'admin');
})->first();
if ($admin) {
    Auth::login($admin);
} else {
    Auth::login($targetUser);
}

// Logic from AssetController::downloadHandoverLetter
$assets = Asset::with('item')
    ->where('holder_type', User::class)
    ->where('holder_id', $targetUser->id)
    ->get();

$coordinator = Coordinator::where('user_id', $targetUser->id)->first();
if ($coordinator) {
    echo "Coordinator found.\n";
    $coordAssets = Asset::with('item')
        ->where('holder_type', Coordinator::class)
        ->where('holder_id', $coordinator->id)
        ->get();
    $assets = $assets->merge($coordAssets);
} else {
    echo "No coordinator found.\n";
}

echo 'Assets count: '.$assets->count()."\n";

// Render view
try {
    // Note: We use the variable name 'user' in the view to refer to the target user (holder)
    // In the controller: downloadHandoverLetter(User $user) -> compact('user')
    $view = view('inventory.assets.pdf.handover_letter', ['user' => $targetUser, 'assets' => $assets, 'coordinator' => $coordinator])->render();
    echo "View rendered successfully.\n";
} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo 'File: '.$e->getFile().' Line: '.$e->getLine()."\n";
    // echo $e->getTraceAsString();
}
