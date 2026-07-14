<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiberPlanItem extends Model
{
    protected $fillable = [
        'fiber_plan_id',
        'inventory_item_id',
        'item_name',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function fiberPlan(): BelongsTo
    {
        return $this->belongsTo(FiberPlan::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
