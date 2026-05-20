<?php
// app/Models/OntOpticalHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OntOpticalHistory extends Model
{
    protected $table = 'ont_optical_history';
    public $timestamps = false;

    protected $fillable = [
        'ont_id', 'rx_power', 'tx_power', 'voltage',
        'temperature', 'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function ont()
    {
        return $this->belongsTo(ONT::class, 'ont_id');
    }
}