<?php
// app/Models/OLTPort.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OLTPort extends Model
{
    protected $table = 'olt_ports'; // <-- PENTING!

    protected $fillable = [
        'olt_id', 'name', 'type', 'index_number',
        'admin_status', 'oper_status',
        'rx_bytes', 'tx_bytes', 'rx_packets', 'tx_packets',
        'rx_errors', 'tx_errors', 'speed',
        'max_onts', 'registered_onts', 'optical_info',
    ];

    protected $casts = [
        'optical_info' => 'array',
    ];

    public function olt()
    {
        return $this->belongsTo(OLT::class, 'olt_id');
    }

    public function onts()
    {
        return $this->hasMany(ONT::class, 'olt_port_id');
    }
}