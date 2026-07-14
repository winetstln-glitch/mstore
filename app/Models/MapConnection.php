<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_type',
        'from_id',
        'to_type',
        'to_id',
        'waypoints',
    ];

    protected $casts = [
        'waypoints' => 'array',
    ];
}
