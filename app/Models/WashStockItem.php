<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashStockItem extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'category',
        'unit',
        'current_stock',
        'minimum_stock',
        'last_buy_price',
        'is_active',
        'wash_supplier_id',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'last_buy_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(WashStockMovement::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(WashSupplier::class);
    }
}
