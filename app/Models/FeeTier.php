<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeTier extends Model
{
    protected $fillable = [
        'fee_profile_id',
        'min_amount',
        'max_amount',
        'fee_type',
        'fee_value',
        'fixed_value',
        'sort_order',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'fee_value' => 'decimal:2',
        'fixed_value' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(FeeProfile::class);
    }
}
