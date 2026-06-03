<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentVoucher extends Model
{
    protected $fillable = [
        'code',
        'amount',
        'status',
        'customer_id',
        'transaction_id',
        'expires_at',
        'used_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'amount' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($voucher) {
            if (empty($voucher->code)) {
                do {
                    $voucher->code = 'PV' . date('YmdHis') . strtoupper(Str::random(4));
                } while (self::where('code', $voucher->code)->exists());
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
