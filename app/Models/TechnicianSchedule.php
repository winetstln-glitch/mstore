<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianSchedule extends Model
{
    const STATUS_OFF = 'off';
    const STATUS_PIKET = 'piket';
    const STATUS_BACKUP = 'backup';
    const STATUS_LONGSHIFT = 'longshift';

    protected $fillable = [
        'user_id',
        'week_number',
        'year',
        'status',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
