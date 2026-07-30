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

    public function scopeMemberships($query)
    {
        return $query->whereIn('package_type', ['member', 'membership']);
    }

    public function scopeResidential($query)
    {
        return $query->whereIn('package_type', ['home', 'residential', 'rumahan']);
    }

    public function scopePppoe($query)
    {
        return $query->where('package_type', 'pppoe');
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
}
