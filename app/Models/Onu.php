<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Onu extends Model
{
    protected $fillable = [
        'olt_id',
        'onu_index',
        'name',
        'serial_number',
        'sn',
        'mac_address',
        'mac',
        'interface',
        'status',
        'signal',
        'tx_power',
        'rx_power',
        'distance',
        'description',
        'last_updated',
    ];

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }
}
