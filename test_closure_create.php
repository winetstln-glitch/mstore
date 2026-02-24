<?php

use App\Models\Closure;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking closures table columns...\n";
$columns = Schema::getColumnListing('closures');
print_r($columns);

echo "\nAttempting to create a closure...\n";
try {
    $closure = Closure::create([
        'name' => 'Test Closure '.time(),
        'latitude' => -6.123,
        'longitude' => 106.123,
        'capacity' => 24,
        'region_id' => 3, // Assuming this exists or is nullable?
        'odc_id' => 6, // Assuming this exists or is nullable?
        'description' => 'Test',
    ]);
    echo 'Closure created successfully: ID '.$closure->id."\n";
} catch (\Exception $e) {
    echo 'Error creating closure: '.$e->getMessage()."\n";
}
