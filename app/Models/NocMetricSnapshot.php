<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NocMetricSnapshot extends Model
{
    protected $fillable = [
        'captured_at',
        'onu_online',
        'onu_offline',
        'onu_los',
        'onu_dying_gasp',
        'onu_weak_signal',
        'pppoe_online',
        'pppoe_offline',
        'pppoe_active_sessions',
        'pppoe_total_users',
        'outage_active',
        'outage_maintenance',
        'outage_fiber_cut',
        'outage_olt_down',
        'ticket_open',
        'ticket_in_progress',
        'ticket_pending',
        'ticket_closed',
        'technician_online',
        'technician_offline',
        'technician_handling_ticket',
        'technician_available',
        'network_health_score',
        'meta',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'meta' => 'array',
    ];
}

