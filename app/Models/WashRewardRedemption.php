<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WashRewardRedemption extends Model
{
    protected $fillable = [
        'wash_reward_voucher_id',
        'wash_transaction_id',
        'redeemed_by_user_id',
        'amount',
        'redeemed_at',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(WashRewardVoucher::class, 'wash_reward_voucher_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WashTransaction::class, 'wash_transaction_id');
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by_user_id');
    }
}

