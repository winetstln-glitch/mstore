<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtkProduct extends Model
{
    protected $fillable = [
        'name', 'code', 'category', 'price', 'cost_price', 'stock', 'unit', 'description', 'image',
    ];
}
