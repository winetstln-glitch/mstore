<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'username',
        'password',
        'profile',
        'duration_seconds',
        'quota_mb',
        'status',
        'batch_id',
        'used_at',
        'expires_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(VoucherBatch::class);
    }
}
