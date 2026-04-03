<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WashStockMovement extends Model
{
    protected $fillable = [
        'wash_stock_item_id',
        'transaction_id',
        'movement_type',
        'quantity',
        'unit_price',
        'total_amount',
        'movement_date',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'movement_date' => 'date',
    ];

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(WashStockItem::class, 'wash_stock_item_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
