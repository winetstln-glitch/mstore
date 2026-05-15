<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Olt extends Model
{
    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'type',
        'brand',
        'model',
        'is_active',
        'description',
        'latitude',
        'longitude',
        'snmp_version',
        'snmp_community',
        'snmp_port',
        'web_user',
        'web_password',
        'last_profile',
    ];

    protected $hidden = [
        'password',
        'snmp_community',
        'web_password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port' => 'integer',
        'snmp_port' => 'integer',
        'password' => 'encrypted',
    ];

    public function onus(): HasMany
    {
        return $this->hasMany(Onu::class);
    }

    public function odcs(): HasMany
    {
        return $this->hasMany(Odc::class);
    }
}
