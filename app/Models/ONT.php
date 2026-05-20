<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ONT extends Model
{
    use SoftDeletes;

    protected $table = 'onus';

    protected $fillable = [
        'olt_id', 'olt_port_id', 'ont_id', 'name',
        'serial_number', 'mac_address', 'vendor', 'model',
        'firmware_version', 'hardware_version', 'password',
        'line_profile', 'service_profile',
        'admin_status', 'oper_status',
        'rx_power', 'tx_power', 'voltage', 'temperature',
        'distance', 'rtt',
        'rx_bytes', 'tx_bytes', 'rx_packets', 'tx_packets',
        'rx_drop_packets', 'tx_drop_packets',
        'vlans', 'last_active_at', 'last_polled_at',
        'interface', 'status', 'signal', 'description',
        'onu_index', 'sn', 'mac', 'last_updated',
    ];

    protected $casts = [
        'vlans' => 'array',
        'last_active_at' => 'datetime',
        'last_polled_at' => 'datetime',
        'last_updated' => 'datetime',
    ];

    public function olt(): BelongsTo
    {
        return $this->belongsTo(OLT::class, 'olt_id');
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(OLTPort::class, 'olt_port_id');
    }

    public function alarms()
    {
        return $this->hasMany(Alarm::class, 'ont_id');
    }

    public function opticalHistory()
    {
        return $this->hasMany(OntOpticalHistory::class, 'ont_id');
    }

    public function trafficHistory()
    {
        return $this->hasMany(OntTrafficHistory::class, 'ont_id');
    }
}
