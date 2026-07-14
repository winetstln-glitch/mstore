<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WeddingBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'customer_id',
        'customer_name',
        'customer_whatsapp',
        'event_date',
        'location',
        'wedding_package_id',
        'notes',
        'status',
        'quotation_amount',
        'dp_amount',
        'confirmed_at',
        'completed_at',
    ];

    protected $casts = [
        'event_date' => 'date',
        'quotation_amount' => 'integer',
        'dp_amount' => 'integer',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $booking): void {
            if (! $booking->booking_number) {
                $booking->booking_number = self::generateBookingNumber();
            }
            if (! $booking->status) {
                $booking->status = 'pending';
            }
        });
    }

    public static function generateBookingNumber(): string
    {
        return 'WED-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(WeddingPackage::class, 'wedding_package_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(WeddingPayment::class);
    }
}

