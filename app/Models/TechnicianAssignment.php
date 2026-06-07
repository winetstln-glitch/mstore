<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianAssignment extends Model
{
    protected $fillable = [
        'ticket_id', 'technician_id', 'assignment_key',
        'status', 'score', 'scoring_details',
        'notes', 'assigned_at', 'accepted_at', 'completed_at', 'assigned_by'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'scoring_details' => 'array',
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($assignment) {
            if (empty($assignment->assignment_key)) {
                $assignment->assignment_key = 'ASSIGN-' . date('YmdHis') . '-' . strtoupper(str()->random(6));
            }
            if (empty($assignment->assigned_at)) {
                $assignment->assigned_at = now();
            }
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
