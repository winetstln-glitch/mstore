<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking permissions...\n";

try {
    $permissions = Permission::where('name', 'like', 'closure.%')->get();
    if ($permissions->isEmpty()) {
        echo "No closure permissions found!\n";
    } else {
        echo "Found closure permissions:\n";
        foreach ($permissions as $p) {
            echo "- " . $p->name . "\n";
        }
    }

    // Check if admin role has these permissions
    $adminRole = Role::where('name', 'admin')->first();
    if ($adminRole) {
        echo "\nAdmin role permissions for closure:\n";
        foreach ($permissions as $p) {
            $has = $adminRole->hasPermissionTo($p->name);
            echo "- " . $p->name . ": " . ($has ? 'YES' : 'NO') . "\n";
        }
    } else {
        echo "\nAdmin role not found.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
