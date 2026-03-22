<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GenieDeviceStatus extends Model
{
    protected $fillable = [
        'customer_id',
        'onu_serial',
        'is_online',
        'last_inform',
        'tr069_ip',
        'connection_request_url',
        'last_reason',
        'last_notified_down_at',
        'last_notified_up_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'last_inform' => 'datetime',
        'last_notified_down_at' => 'datetime',
        'last_notified_up_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
