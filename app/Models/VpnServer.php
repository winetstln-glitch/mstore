<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VpnServer extends Model
{
    protected $fillable = [
        'name',
        'location',
        'ip_public',
        'port',
        'protocol',
        'status',
        'last_reported_load',
        'last_latency_ms',
    ];

    protected $casts = [
        'port' => 'integer',
        'last_reported_load' => 'integer',
        'last_latency_ms' => 'integer',
    ];

    public function vpnAccounts(): HasMany
    {
        return $this->hasMany(VpnAccount::class);
    }
}
