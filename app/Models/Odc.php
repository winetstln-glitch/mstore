<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odc extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'olt_id',
        'region_id',
        'pon_port',
        'area',
        'color',
        'cable_no',
        'latitude',
        'longitude',
        'capacity',
        'description',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function odps(): HasMany
    {
        return $this->hasMany(Odp::class);
    }
}
