<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CctvPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'camera_count',
        'dvr_nvr',
        'hdd',
        'price',
        'warranty_months',
        'is_active',
    ];

    protected $casts = [
        'camera_count' => 'integer',
        'price' => 'integer',
        'warranty_months' => 'integer',
        'is_active' => 'boolean',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(CctvBooking::class);
    }
}

