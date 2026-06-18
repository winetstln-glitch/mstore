<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeLog extends Model
{
    protected $fillable = [
        'fee_profile_id',
        'transaction_type',
        'transaction_id',
        'nominal',
        'calculated_fee',
        'manual_fee',
        'final_fee',
        'reason',
        'module',
        'user_id',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'calculated_fee' => 'decimal:2',
        'manual_fee' => 'decimal:2',
        'final_fee' => 'decimal:2',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(FeeProfile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
