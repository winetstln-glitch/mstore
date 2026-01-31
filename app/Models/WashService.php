<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WashService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'image',
        'vehicle_type',  
        'price', 
        'description', 
        'is_active'
    ];
}
