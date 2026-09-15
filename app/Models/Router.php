<?php

namespace App\Models;

use App\Traits\ScopesByUserArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    use ScopesByUserArea;

    protected $fillable = [
        'region_id', 'company_id',
        'name',
        'host',
        'vpn_tunnel_ip',
        'vpn_account_id',
        'vpn_status',
        'port',
        'username',
        'location',
        'latitude',
        'longitude',
        'password',
        'is_active',
        'is_online',
        'last_online_at',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'port' => 'integer',
        'password' => 'encrypted', // Securely store password
        'last_online_at' => 'datetime',
    ];

    public function vpnAccount()
    {
        return $this->belongsTo(VpnAccount::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
