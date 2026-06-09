<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscalationNotification extends Model
{
    protected $fillable = [
        'ticket_id',
        'sla_rule_id',
        'channel',
        'target',
        'recipient_role',
        'status',
        'attempt',
        'error_message',
        'sent_at',
        'payload',
    ];

    protected $casts = [
        'attempt' => 'integer',
        'sent_at' => 'datetime',
        'payload' => 'array',
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

