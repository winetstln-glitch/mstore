<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CctvPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'cctv_booking_id',
        'type',
        'amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(CctvBooking::class, 'cctv_booking_id');
    }

    public function paymentTransaction(): MorphOne
    {
        return $this->morphOne(PaymentTransaction::class, 'paymentable');
    }
}

