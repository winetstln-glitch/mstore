<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WashServicePriceRule extends Model
{
    protected $fillable = [
        'wash_service_id',
        'vehicle_type',
        'size_tier',
        'package_type',
        'price',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'bool',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(WashService::class, 'wash_service_id');
    }

    public function getLabelAttribute(): string
    {
        $size = WashService::SIZE_TIER_OPTIONS[$this->size_tier] ?? ucfirst(str_replace('_', ' ', (string) $this->size_tier));
        $package = WashService::PACKAGE_TYPE_OPTIONS[$this->package_type] ?? ucfirst(str_replace('_', ' ', (string) $this->package_type));
        if ($size === '-' || $size === '') {
            return $package;
        }

        return $size.' - '.$package;
    }
}
