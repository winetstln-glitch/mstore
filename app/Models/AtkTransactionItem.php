<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtkTransactionItem extends Model
{
    protected $fillable = [
        'atk_transaction_id', 'product_id', 'product_name', 'price', 'quantity', 'subtotal', 'nominal_transaksi', 'fee'
    ];

    public function product()
    {
        return $this->belongsTo(AtkProduct::class);
    }
}
