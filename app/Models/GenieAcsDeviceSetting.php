<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GenieAcsDeviceSetting extends Model
{
    protected $table = 'genieacs_device_settings';
    
    protected $fillable = ['device_id', 'alias'];
}
