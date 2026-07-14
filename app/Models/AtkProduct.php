<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtkProduct extends Model
{
    protected $fillable = [
        'name', 'code', 'category', 'price', 'cost_price', 'stock', 'unit', 'description', 'image',
        'barcode', 'category_id', 'selling_price', 'current_stock', 'stock_alert', 'supplier_id',
        'minimum_stock',
    ];

    public function categoryModel(): BelongsTo
    {
        return $this->belongsTo(AtkCategory::class, 'category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(AtkSupplier::class, 'supplier_id');
    }
}
