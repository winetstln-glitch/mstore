<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaBreach extends Model
{
    protected $fillable = [
        'ticket_id',
        'sla_rule_id',
        'breached_at',
        'resolved_at',
        'current_status',
    ];

    protected $casts = [
        'breached_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(SlaRule::class, 'sla_rule_id');
    }
}

