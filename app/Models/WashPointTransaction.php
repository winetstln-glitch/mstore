<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WashPointTransaction extends Model
{
    use Auditable;

    protected $fillable = [
        'wash_member_id',
        'wash_customer_id',
        'wash_transaction_id',
        'type',
        'points',
        'balance_after',
        'description',
        'transaction_date'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(WashMember::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(WashCustomer::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WashTransaction::class);
    }
}
