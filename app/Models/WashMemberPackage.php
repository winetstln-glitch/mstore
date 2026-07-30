<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashMemberPackage extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'network_type',
        'hotspot_profile_id',
        'pppoe_profile',
        'rate_limit_mbps',
        'daily_wifi_minutes',
        'router_id',
        'description',
        'price',
        'duration_days',
        'discount_percent',
        'benefits',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'discount_percent' => 'decimal:2',
        'rate_limit_mbps' => 'decimal:2',
        'daily_wifi_minutes' => 'integer',
        'benefits' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(WashMemberSubscription::class);
    }

    public function hotspotProfile(): BelongsTo
    {
        return $this->belongsTo(HotspotProfile::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWashOnly($query)
    {
        return $query->where('type', 'wash');
    }

    public function scopeWifiOnly($query)
    {
        return $query->where('type', 'wifi');
    }

    public function scopeCombined($query)
    {
        return $query->where('type', 'both');
    }

    public function scopeHasWifiBenefit($query)
    {
        return $query->whereIn('type', ['wifi', 'both']);
    }

    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getTypeLabelAttribute()
    {
        return match ($this->type) {
            'wash' => 'Cuci Saja',
            'wifi' => 'WiFi Saja',
            'both' => 'Cuci + WiFi',
            default => ucfirst($this->type),
        };
    }
}
