<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherTemplate extends Model
{
    protected $fillable = [
        'name',
        'rate_limit',
        'duration_seconds',
        'quota_mb',
        'price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];
}
