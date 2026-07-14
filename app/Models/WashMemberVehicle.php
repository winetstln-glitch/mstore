<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WashMemberVehicle extends Model
{
    protected $fillable = [
        'wash_member_id',
        'vehicle_plate',
        'vehicle_type',
        'brand',
        'model',
        'color',
        'year',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(WashMember::class, 'wash_member_id');
    }
}

