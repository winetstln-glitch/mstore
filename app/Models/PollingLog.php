<?php
// app/Models/PollingLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollingLog extends Model
{
    protected $table = 'polling_logs';
    public $timestamps = false;

    protected $fillable = [
        'olt_id', 'status', 'duration_ms', 'onts_found',
        'error_message', 'polled_at',
    ];

    protected $casts = [
        'polled_at' => 'datetime',
    ];

    public function olt()
    {
        return $this->belongsTo(OLT::class, 'olt_id');
    }
}