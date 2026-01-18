<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'location',
        'latitude',
        'longitude',
        'password',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port' => 'integer',
        'password' => 'encrypted', // Securely store password
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
