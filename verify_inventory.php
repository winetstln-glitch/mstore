<?php

use App\Models\InventoryTransaction;

echo 'Verifying Inventory Revenue Logic...'.PHP_EOL;

$allOut = InventoryTransaction::where('type', 'out')->with('item')->get();

$totalAll = 0;
$totalMaterial = 0;
$totalTool = 0;

foreach ($allOut as $t) {
    if (! $t->item) {
        continue;
    }

    $val = $t->quantity * ($t->item->price ?? 0);
    $totalAll += $val;

    $type = $t->item->type_group ?? 'material';
    if ($type === 'tool') {
        $totalTool += $val;
        echo 'Excluding Tool: '.$t->item->name.' - '.number_format($val).PHP_EOL;
    } else {
        $totalMaterial += $val;
    }
}

echo 'Total All: '.number_format($totalAll).PHP_EOL;
echo 'Total Tool (Excluded): '.number_format($totalTool).PHP_EOL;
echo 'Total Material (Revenue): '.number_format($totalMaterial).PHP_EOL;
