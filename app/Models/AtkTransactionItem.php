<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtkTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'atk_transaction_id',
        'atk_product_id',
        'quantity',
        'price',
        'subtotal',
    ];

    public function transaction()
    {
        return $this->belongsTo(AtkTransaction::class, 'atk_transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(AtkProduct::class, 'atk_product_id');
    }
}
