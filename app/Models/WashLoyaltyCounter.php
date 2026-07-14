<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashLoyaltyCounter extends Model
{
    protected $fillable = [
        'wash_customer_id',
        'wash_member_id',
        'vehicle_plate',
        'cycle_paid_count',
        'lifetime_paid_count',
        'last_paid_transaction_id',
        'last_paid_at',
    ];

    protected $casts = [
        'last_paid_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(WashCustomer::class, 'wash_customer_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(WashMember::class, 'wash_member_id');
    }

    public function lastPaidTransaction(): BelongsTo
    {
        return $this->belongsTo(WashTransaction::class, 'last_paid_transaction_id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(WashRewardVoucher::class, 'wash_loyalty_counter_id');
    }
}
