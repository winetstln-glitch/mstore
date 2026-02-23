<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpnAccount extends Model
{
    protected $fillable = [
        'user_id',
        'router_id',
        'vpn_server_id',
        'username',
        'password',
        'token',
        'status',
    ];

    protected $casts = [
        'password' => 'encrypted',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(VpnServer::class, 'vpn_server_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
