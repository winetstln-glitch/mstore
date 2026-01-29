<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GenieDeviceStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'ip_address',
        'last_inform',
        'is_online',
    ];

    protected $casts = [
        'last_inform' => 'datetime',
        'is_online' => 'boolean',
    ];
}
