<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtkProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'stock',
        'buy_price',
        'sell_price_retail',
        'sell_price_wholesale',
        'unit',
    ];

    public function items()
    {
        return $this->hasMany(AtkTransactionItem::class);
    }
}
