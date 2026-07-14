<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherBatch extends Model
{
    protected $fillable = [
        'batch_code',
        'profile',
        'duration_seconds',
        'quota_mb',
        'total_vouchers',
        'created_by',
    ];
}
