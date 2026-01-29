<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotspotProfile extends Model
{
    protected $fillable = [
        'router_id', 
        'name', 
        'shared_users', 
        'rate_limit', 
        'price', 
        'validity_value', 
        'validity_unit', 
        'mikrotik_id'
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }
}
