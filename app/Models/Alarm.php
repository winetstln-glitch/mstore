<?php
// app/Models/Alarm.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alarm extends Model
{
    protected $table = 'alarms';

    protected $fillable = [
        'olt_id', 'ont_id', 'severity', 'type', 'message',
        'details', 'acknowledged', 'acknowledged_by',
        'acknowledged_at', 'resolved', 'resolved_at', 'occurred_at',
    ];

    protected $casts = [
        'details' => 'array',
        'acknowledged' => 'boolean',
        'resolved' => 'boolean',
        'occurred_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function olt()
    {
        return $this->belongsTo(OLT::class, 'olt_id');
    }

    public function ont()
    {
        return $this->belongsTo(ONT::class, 'ont_id');
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}