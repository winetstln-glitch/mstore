<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Onu extends Model
{
    protected $fillable = [
        'olt_id', 'name', 'serial_number', 'mac_address',
        'interface', 'status', 'signal', 'distance', 'description',
        'onu_index', 'tx_power', 'rx_power', 'mac', 'sn',
        'last_updated',
    ];

    protected $casts = [
        'tx_power' => 'float',
        'rx_power' => 'float',
        'distance' => 'integer',
        'last_updated' => 'datetime',
    ];

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }
}
