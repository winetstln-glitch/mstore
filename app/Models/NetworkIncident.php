<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkIncident extends Model
{
    protected $fillable = [
        'title', 'description', 'type', 'status', 'severity',
        'region_id', 'olt_id', 'odp_id',
        'detected_at', 'resolved_at', 'estimated_resolution_at',
        'affected_customers', 'meta', 'created_by'
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'estimated_resolution_at' => 'datetime',
        'affected_customers' => 'array',
        'meta' => 'array'
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['detected', 'investigating', 'in_progress']);
    }
}
