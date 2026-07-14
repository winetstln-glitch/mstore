<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WashMemberSubscription extends Model
{
    protected $fillable = [
        'wash_member_id',
        'wash_member_package_id',
        'wash_transaction_id',
        'start_date',
        'end_date',
        'status',
        'paid_amount',
        'meta',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'paid_amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(WashMember::class, 'wash_member_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(WashMemberPackage::class, 'wash_member_package_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WashTransaction::class, 'wash_transaction_id');
    }
}
