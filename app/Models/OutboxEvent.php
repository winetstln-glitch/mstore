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
}