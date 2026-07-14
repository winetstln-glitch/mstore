<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkDiagnostic extends Model
{
    protected $fillable = [
        'customer_id', 'ticket_id',
        'diagnosis_key', 'status',
        'summary', 'checks',
        'genieacs_data', 'mikrotik_data', 'billing_data', 'area_outage_data',
        'recommendations', 'priority', 'ticket_needed',
        'started_at', 'completed_at'
    ];

    protected $casts = [
        'checks' => 'array',
        'genieacs_data' => 'array',
        'mikrotik_data' => 'array',
        'billing_data' => 'array',
        'area_outage_data' => 'array',
        'recommendations' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
