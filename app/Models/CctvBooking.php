<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class CctvBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'customer_id',
        'customer_name',
        'customer_whatsapp',
        'address',
        'cctv_package_id',
        'notes',
        'status',
        'quotation_amount',
        'dp_amount',
        'scheduled_at',
        'completed_at',
    ];

    protected $casts = [
        'quotation_amount' => 'integer',
        'dp_amount' => 'integer',
        'scheduled_at' => 'datetime',
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
        return 'CCTV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(CctvPackage::class, 'cctv_package_id');
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(CctvSurvey::class);
    }

    public function installation(): HasOne
    {
        return $this->hasOne(CctvInstallation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CctvPayment::class);
    }
}

