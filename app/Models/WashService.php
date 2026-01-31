<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WashService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'category_id',
        'type', // service, physical
        'image',
        'vehicle_type',  
        'price', 
        'cost_price',
        'stock',
        'description', 
        'is_active'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
