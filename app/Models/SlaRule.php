<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaRule extends Model
{
    protected $fillable = [
        'name',
        'threshold_minutes',
        'status',
        'is_active',
        'description',
        'ticket_category',
        'warning_threshold_hours',
        'critical_threshold_hours',
        'escalation_threshold_hours',
        'escalation_recipients',
        'priority',
    ];

    protected $casts = [
        'threshold_minutes' => 'integer',
        'is_active' => 'boolean',
        'warning_threshold_hours' => 'integer',
        'critical_threshold_hours' => 'integer',
        'escalation_threshold_hours' => 'integer',
        'escalation_recipients' => 'array',
    ];

    public function breaches(): HasMany
    {
        return $this->hasMany(SlaBreach::class);
    }
}
