<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulePeriod extends Model
{
    protected $fillable = ['year', 'week_number', 'start_date', 'end_date'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
