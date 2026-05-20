<?php
// app/Models/OntTrafficHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OntTrafficHistory extends Model
{
    protected $table = 'ont_traffic_history';
    public $timestamps = false;

    protected $fillable = [
        'ont_id', 'rx_bytes', 'tx_bytes', 'rx_packets',
        'tx_packets', 'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function ont()
    {
        return $this->belongsTo(ONT::class, 'ont_id');
    }
}