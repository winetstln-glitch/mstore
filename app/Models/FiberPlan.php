<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiberPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'region_id',
        'olt_id',
        'path',
        'length_meters',
        'status',
        'meta_data',
        'created_by',
    ];

    protected $casts = [
        'path' => 'array',
        'meta_data' => 'array',
        'length_meters' => 'float',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(OLT::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FiberPlanItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
