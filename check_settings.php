<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $toolRate = App\Models\Setting::getValue('commission_tool_percent');
    $ispRate = App\Models\Setting::getValue('commission_isp_percent');
    echo "Tool Rate Setting: " . ($toolRate ?? 'null') . "\n";
    echo "ISP Rate Setting: " . ($ispRate ?? 'null') . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
