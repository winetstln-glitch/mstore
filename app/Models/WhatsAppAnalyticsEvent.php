<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppAnalyticsEvent extends Model
{
    protected $table = 'whatsapp_analytics_events';

    protected $fillable = [
        'occurred_at',
        'direction',
        'phone_number',
        'whatsapp_session_id',
        'intent',
        'used_ai',
        'is_fallback',
        'ticket_id',
        'payment_transaction_id',
        'voucher_payment_id',
        'meta',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'used_ai' => 'boolean',
        'is_fallback' => 'boolean',
        'meta' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(WhatsAppSession::class, 'whatsapp_session_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}

