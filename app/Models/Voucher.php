<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'hotspot_profile_id', 
        'code', 
        'password', 
        'price', 
        'status', 
        'generated_by', 
        'batch_id'
    ];

    public function profile()
    {
        return $this->belongsTo(HotspotProfile::class, 'hotspot_profile_id');
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
