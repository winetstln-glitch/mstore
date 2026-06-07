<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AreaOutage extends Model
{
    protected $fillable = [
        'title', 'description', 'type', 'status',
        'started_at', 'estimated_finish_at',
        'region_id', 'odp_id', 'olt_id',
        'affected_areas'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'estimated_finish_at' => 'datetime',
        'affected_areas' => 'array'
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function affectsCustomer(Customer $customer): bool
    {
        // Check region
        if ($this->region_id && $this->region_id === $customer->region_id) {
            return true;
        }
        // Check ODP
        if ($this->odp_id && $this->odp_id === $customer->odp_id) {
            return true;
        }
        // Check OLT
        if ($this->olt_id && $this->olt_id === $customer->olt_id) {
            return true;
        }
        // Check affected areas JSON
        if ($this->affected_areas) {
            foreach ($this->affected_areas as $area) {
                if (stripos($customer->address, $area) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
