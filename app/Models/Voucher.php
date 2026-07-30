<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    protected $fillable = [
        'username',
        'password',
        'profile',
        'duration_seconds',
        'quota_mb',
        'status',
        'batch_id',
        'hotspot_profile_id',
        'router_id',
        'invoice_id',
        'customer_name',
        'customer_phone',
        'used_at',
        'expires_at',
        'sold_at',
        'synced_to_router',
        'sync_error',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'sold_at' => 'datetime',
        'synced_to_router' => 'boolean',
        'duration_seconds' => 'integer',
        'quota_mb' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class);
    }

    public function hotspotProfile(): BelongsTo
    {
        return $this->belongsTo(HotspotProfile::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function scopeUnused($query)
    {
        return $query->where('status', 'unused');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeSynced($query)
    {
        return $query->where('synced_to_router', true);
    }

    public function scopeUnsynced($query)
    {
        return $query->where('synced_to_router', false);
    }

    public function scopeForCustomer($query, $phone)
    {
        return $query->where('customer_phone', $phone);
    }

    public function getFormattedUptimeAttribute()
    {
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

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'unused' => 'Belum Dipakai',
            'used' => 'Sudah Dipakai',
            'expired' => 'Kedaluwarsa',
            'sold' => 'Terjual',
            default => ucfirst($this->status),
        };
    }

    public function markAsSynced(?string $error = null): bool
    {
        $this->synced_to_router = $error === null;
        $this->sync_error = $error;
        return $this->save();
    }

    public function markAsSold(?string $customerName = null, ?string $customerPhone = null): bool
    {
        $this->status = 'sold';
        $this->sold_at = now();
        if ($customerName) {
            $this->customer_name = $customerName;
        }
        if ($customerPhone) {
            $this->customer_phone = $customerPhone;
        }
        return $this->save();
    }
}
