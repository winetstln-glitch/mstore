<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    protected $fillable = [
        'name',
        'date',
        'is_national',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'is_national' => 'boolean',
    ];
}
