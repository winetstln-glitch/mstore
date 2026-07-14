<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtkTransactionItem extends Model
{
    protected $fillable = [
        'atk_transaction_id', 'product_id', 'product_name', 'price', 'quantity', 'subtotal', 'nominal_transaksi', 'fee', 'fee_type',
        'item_type', 'service_id', 'cost', 'atk_float_account_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(AtkProduct::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AtkService::class);
    }

    public function atkTransaction(): BelongsTo
    {
        return $this->belongsTo(AtkTransaction::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->atkTransaction();
    }
}
