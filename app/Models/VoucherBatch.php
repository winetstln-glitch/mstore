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

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
