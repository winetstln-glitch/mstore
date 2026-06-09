<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CctvInstallation extends Model
{
    use HasFactory;

    protected $fillable = [
        'cctv_booking_id',
        'technician_id',
        'scheduled_at',
        'started_at',
        'completed_at',
        'progress_percent',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percent' => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(CctvBooking::class, 'cctv_booking_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}

