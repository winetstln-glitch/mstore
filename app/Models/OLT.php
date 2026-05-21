<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OLT extends Model
{
    use SoftDeletes;

    protected $table = 'olts';

    protected $fillable = [
        'name', 'ip_address', 'vendor', 'model', 'location',
        'read_community', 'write_community', 'snmp_version', 'snmpv3_config',
        'poll_interval', 'snmp_timeout', 'snmp_retries',
        'firmware', 'serial_number', 'mac_address',
        'cpu_usage', 'memory_usage', 'temperature', 'uptime',
        'status', 'is_active', 'last_polled_at', 'last_online_at',
        'host', 'port', 'username', 'password', 'type', 'brand',
        'latitude', 'longitude', 'snmp_port', 'snmp_community', 'web_user',
        'last_profile', 'last_synced_at', 'custom_oid_name',
        'custom_oid_status', 'custom_oid_rx', 'custom_oid_tx',
        'custom_oid_mac', 'custom_oid_sn', 'custom_divider', 'api_token',
        'last_status', 'last_status_message', 'last_status_check',
        'total_onus', 'online_onus', 'offline_onus', 'los_onus',
    ];

    protected $casts = [
        'snmpv3_config' => 'array',
        'is_active' => 'boolean',
        'last_polled_at' => 'datetime',
        'last_online_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'last_status_check' => 'datetime',
    ];

    public function onts(): HasMany
    {
        return $this->hasMany(ONT::class, 'olt_id');
    }

    public function ports(): HasMany
    {
        return $this->hasMany(OLTPort::class, 'olt_id');
    }

    public function alarms()
    {
        return $this->hasMany(Alarm::class, 'olt_id');
    }

    public function pollingLogs()
    {
        return $this->hasMany(PollingLog::class, 'olt_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }
}
