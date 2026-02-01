<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Asset extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'asset_code',
        'serial_number',
        'mac_address',
        'status',
        'condition',
        'latitude',
        'longitude',
        'holder_type',
        'holder_id',
        'meta_data',
        'purchase_date',
        'warranty_expiry',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function holder(): MorphTo
    {
        return $this->morphTo();
    }
}
