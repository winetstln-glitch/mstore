<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotspotProfile extends Model
{
    protected $fillable = [
        'name',
        'mikrotik_profile_name',
        'package_type',
        'rate_limit_mbps',
        'shared_users',
        'limit_uptime',
        'duration_seconds',
        'validity_seconds',
        'quota_mb',
        'price',
        'description',
        'color_badge',
        'router_id',
        'sort_order',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'rate_limit_mbps' => 'decimal:2',
        'shared_users' => 'integer',
        'duration_seconds' => 'integer',
        'validity_seconds' => 'integer',
        'quota_mb' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function washMemberPackages(): HasMany
    {
        return $this->hasMany(WashMemberPackage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVouchers($query)
    {
        return $query->where('package_type', 'voucher');
    }

    public function scopeHotspot($query)
    {
        return $query->whereIn('package_type', ['member', 'membership', 'hotspot']);
    }

    public function scopeMemberships($query)
    {
        return $query->whereIn('package_type', ['member', 'membership', 'hotspot']);
    }

    public function scopeResidential($query)
    {
        return $query->whereIn('package_type', ['residential', 'home', 'rumahan']);
    }

    public function scopePppoe($query)
    {
        return $query->where('package_type', 'pppoe');
    }

    public function scopePaketb($query)
    {
        return $query->whereIn('package_type', ['pppoe', 'residential', 'home', 'rumahan']);
    }

    public function getFormattedUptimeAttribute()
    {
        if ($this->limit_uptime) {
            return $this->limit_uptime;
        }
        if ($this->duration_seconds) {
            $hours = floor($this->duration_seconds / 3600);
            $minutes = floor(($this->duration_seconds % 3600) / 60);
            if ($hours > 24) {
                $days = floor($hours / 24);
                return $days . ' hari';
            }
            if ($hours > 0) {
                return $hours . ' jam ' . $minutes . ' menit';
            }
            return $minutes . ' menit';
        }
        return 'Unlimited';
    }

    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getFormattedValidityAttribute()
    {
        if ($this->validity_seconds) {
            $hours = floor($this->validity_seconds / 3600);
            if ($hours > 24) {
                $days = floor($hours / 24);
                return $days . ' hari';
            }
            if ($hours > 0) {
                return $hours . ' jam';
            }
            return 'Unlimited';
        }
        return 'Unlimited';
    }

    public function getDurationDaysAttribute()
    {
        if (!$this->duration_seconds) return 0;
        return floor($this->duration_seconds / 86400);
    }

    public function getDurationHoursAttribute()
    {
        if (!$this->duration_seconds) return 0;
        $remaining = $this->duration_seconds % 86400;
        return floor($remaining / 3600);
    }

    public function getValidityDaysAttribute()
    {
        if (!$this->validity_seconds) return 0;
        return floor($this->validity_seconds / 86400);
    }

    public function getValidityHoursAttribute()
    {
        if (!$this->validity_seconds) return 0;
        $remaining = $this->validity_seconds % 86400;
        return floor($remaining / 3600);
    }

    public static function convertToSeconds($hours = 0, $days = 0): ?int
    {
        $total = ((int)$hours * 3600) + ((int)$days * 86400);
        return $total > 0 ? $total : null;
    }
}
