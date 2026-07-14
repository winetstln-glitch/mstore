<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtkService extends Model
{
    protected $fillable = [
        'name', 'code', 'category', 'price', 'description', 'image',
    ];
}
