<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CctvSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'cctv_booking_id',
        'survey_date',
        'surveyor_id',
        'location',
        'photos',
        'notes',
        'status',
    ];

    protected $casts = [
        'survey_date' => 'datetime',
        'photos' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(CctvBooking::class, 'cctv_booking_id');
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surveyor_id');
    }
}

