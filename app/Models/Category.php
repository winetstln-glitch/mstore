<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'description'];

    public function atkProducts()
    {
        return $this->hasMany(AtkProduct::class);
    }

    public function washServices()
    {
        return $this->hasMany(WashService::class);
    }
}
