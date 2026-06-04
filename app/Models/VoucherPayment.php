<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VoucherPayment extends Model
{
    protected $fillable = [
        'reference_id',
        'customer_name',
        'phone_number',
        'voucher_template_id',
        'amount',
        'status',
        'payment_method',
        'payment_reference',
        'voucher_id',
        'expires_at',
        'paid_at',
        'qr_data',
        'qr_url',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->reference_id)) {
                $payment->reference_id = 'VPAY-' . date('YmdHis') . '-' . strtoupper(Str::random(6));
            }
            if (empty($payment->expires_at)) {
                $payment->expires_at = now()->addHours(24); // Expire after 24 hours
            }
        });
    }

    public function voucherTemplate()
    {
        return $this->belongsTo(VoucherTemplate::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}
