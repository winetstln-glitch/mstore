<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_id',
        'user_id',
        'paymentable_type',
        'paymentable_id',
        'customer_name',
        'phone_number',
        'email',
        'amount',
        'payment_type',
        'payment_method',
        'payment_gateway',
        'gateway_reference_id',
        'status',
        'qr_url',
        'qr_data',
        'paid_at',
        'expires_at',
        'meta',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->reference_id)) {
                $transaction->reference_id = 'PAY-' . date('YmdHis') . '-' . strtoupper(Str::random(6));
            }
            if (empty($transaction->expires_at)) {
                $transaction->expires_at = now()->addHours(24); // 24 hour expiry
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsPaid(string $gatewayReferenceId = null): void
    {
        $this->update([
            'status' => 'paid',
            'gateway_reference_id' => $gatewayReferenceId,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
