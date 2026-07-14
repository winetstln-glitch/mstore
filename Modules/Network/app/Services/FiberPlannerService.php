<?php

namespace Modules\Network\Services;

use App\Models\InventoryItem;
use App\Models\FiberPlan;
use App\Models\FiberPlanItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FiberPlannerService
{
    // Typical material requirements per 100 meters
    protected const DEFAULT_MATERIALS = [
        // Fiber Cable
        [
            'category' => 'Fiber Optic',
            'type' => 'Fiber Cable',
            'per_100m' => 1,
            'unit' => 'meter',
        ],
        // Pole
        [
            'category' => 'Infrastructure',
            'type' => 'Pole',
            'per_100m' => 1/40, // 1 pole every 40 meters
            'unit' => 'piece',
        ],
        // Closure
        [
            'category' => 'Infrastructure',
            'type' => 'Closure',
            'per_100m' => 1/150, // 1 closure every 150 meters
            'unit' => 'piece',
        ],
    ];

    public function calculateMaterials(float $lengthMeters, array $customMaterials = []): array
    {
        $materials = [];
        $materialConfigs = array_merge(self::DEFAULT_MATERIALS, $customMaterials);

        foreach ($materialConfigs as $config) {
            // Calculate quantity
            $quantity = $lengthMeters * ($config['per_100m'] / 100);
            $quantity = max(ceil($quantity), 1);

            // Try to find matching inventory item
            $inventoryItem = InventoryItem::where('category', $config['category'])
                ->where('type', $config['type'])
                ->first();

            $materials[] = [
                'category' => $config['category'],
                'type' => $config['type'],
                'item_name' => $inventoryItem ? $inventoryItem->name : "{$config['type']} ({$config['category']})",
                'inventory_item_id' => $inventoryItem ? $inventoryItem->id : null,
                'quantity' => $quantity,
                'unit' => $inventoryItem ? $inventoryItem->unit : $config['unit'],
                'unit_price' => $inventoryItem ? $inventoryItem->price : 0,
                'total_price' => $inventoryItem ? $inventoryItem->price * $quantity : 0,
            ];
        }

        // Calculate ODC, ODP, HTB based on coverage
        // Default: 1 ODP every 200 meters
        $odpCount = max(ceil($lengthMeters / 200), 1);
        $odpItem = InventoryItem::where('category', 'Infrastructure')->where('type', 'ODP')->first();
        $materials[] = [
            'category' => 'Infrastructure',
            'type' => 'ODP',
            'item_name' => $odpItem ? $odpItem->name : 'ODP',
            'inventory_item_id' => $odpItem ? $odpItem->id : null,
            'quantity' => $odpCount,
            'unit' => $odpItem ? $odpItem->unit : 'piece',
            'unit_price' => $odpItem ? $odpItem->price : 0,
            'total_price' => $odpItem ? $odpItem->price * $odpCount : 0,
        ];

        // Default: 1 ODC for the plan
        $odcItem = InventoryItem::where('category', 'Infrastructure')->where('type', 'ODC')->first();
        $materials[] = [
            'category' => 'Infrastructure',
            'type' => 'ODC',
            'item_name' => $odcItem ? $odcItem->name : 'ODC',
            'inventory_item_id' => $odcItem ? $odcItem->id : null,
            'quantity' => 1,
            'unit' => $odcItem ? $odcItem->unit : 'piece',
            'unit_price' => $odcItem ? $odcItem->price : 0,
            'total_price' => $odcItem ? $odcItem->price * 1 : 0,
        ];

        return $materials;
    }

    public function calculateTotalCost(array $materials): float
    {
        return array_sum(array_column($materials, 'total_price'));
    }

    public function createPlan(array $data): FiberPlan
    {
        return DB::transaction(function () use ($data) {
            $data['created_by'] = auth()->id() ?? null;
            $data['status'] = $data['status'] ?? 'draft';

            $plan = FiberPlan::create($data);

            if (isset($data['materials']) && is_array($data['materials'])) {
                foreach ($data['materials'] as $material) {
                    FiberPlanItem::create([
                        'fiber_plan_id' => $plan->id,
                        'inventory_item_id' => $material['inventory_item_id'] ?? null,
                        'item_name' => $material['item_name'],
                        'quantity' => $material['quantity'],
                        'unit_price' => $material['unit_price'] ?? 0,
                        'total_price' => $material['total_price'] ?? 0,
                        'notes' => $material['notes'] ?? null,
                    ]);
                }
            }

            return $plan;
        });
    }

    public function generateBoq(FiberPlan $plan): array
    {
        $plan->load('items.inventoryItem');

        $materials = $plan->items->map(function ($item) {
            return [
                'id' => $item->id,
                'category' => $item->inventoryItem?->category ?? 'Unknown',
                'type' => $item->inventoryItem?->type ?? 'Unknown',
                'item_name' => $item->item_name,
                'quantity' => $item->quantity,
                'unit' => $item->inventoryItem?->unit ?? 'piece',
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'notes' => $item->notes,
            ];
        });

        $totalCost = $this->calculateTotalCost($materials->toArray());

        return [
            'plan' => $plan->only('id', 'name', 'description', 'length_meters', 'status'),
            'materials' => $materials,
            'total_cost' => $totalCost,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
