<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WashService extends Model
{
    protected $fillable = [
        'name', 'vehicle_type', 'price', 'description', 'image', 'is_active'
    ];
}
