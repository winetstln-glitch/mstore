<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class OutboxEvent extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'company_branch_id',
        'aggregate_type', 'aggregate_id', 'event_type', 'payload', 'event_hash', 'status', 'error_message', 'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            if (empty($event->event_hash)) {
                $event->event_hash = hash('sha256', json_encode([
                    'aggregate_type' => $event->aggregate_type,
                    'aggregate_id' => (string) $event->aggregate_id,
                    'event_type' => $event->event_type,
                    'payload' => is_array($event->payload) ? $event->payload : json_decode($event->payload, true),
                ]));
            }
        });
    }
}