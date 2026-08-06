<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashRewardVoucher extends Model
{
    protected $fillable = [
        'code',
        'wash_loyalty_counter_id',
        'wash_customer_id',
        'wash_member_id',
        'vehicle_plate',
        'reward_type',
        'source',
        'source_reason',
        'status',
        'issued_at',
        'expires_at',
        'used_at',
        'used_wash_transaction_id',
        'revoked_at',
        'revoked_reason',
        'meta',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'meta' => 'array',
    ];

    public function counter(): BelongsTo
    {
        return $this->belongsTo(WashLoyaltyCounter::class, 'wash_loyalty_counter_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(WashCustomer::class, 'wash_customer_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(WashMember::class, 'wash_member_id');
    }

    public function usedTransaction(): BelongsTo
    {
        return $this->belongsTo(WashTransaction::class, 'used_wash_transaction_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(WashRewardRedemption::class, 'wash_reward_voucher_id');
    }
}
