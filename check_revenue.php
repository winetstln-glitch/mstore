<?php
use App\Models\AtkTransaction;
use App\Models\WashTransaction;
use App\Models\InventoryTransaction;

echo "Checking Data Sources for Revenue..." . PHP_EOL;

try {
    $atk = AtkTransaction::sum('total_amount');
    echo "ATK Revenue: " . number_format($atk, 0, ',', '.') . PHP_EOL;
} catch (\Exception $e) {
    echo "ATK Error: " . $e->getMessage() . PHP_EOL;
}

try {
    $wash = WashTransaction::sum('total_amount');
    echo "Wash Revenue: " . number_format($wash, 0, ',', '.') . PHP_EOL;
} catch (\Exception $e) {
    echo "Wash Error: " . $e->getMessage() . PHP_EOL;
}

try {
    $inv = InventoryTransaction::with('item')->get()->sum(function($t) {
        return $t->quantity * ($t->item->price ?? 0);
    });
    echo "Inventory Revenue: " . number_format($inv, 0, ',', '.') . PHP_EOL;
} catch (\Exception $e) {
    echo "Inventory Error: " . $e->getMessage() . PHP_EOL;
}
