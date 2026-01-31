<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Closure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'coordinates',
        'parent_type',
        'parent_id',
        'description',
    ];

    public function parent()
    {
        return $this->morphTo();
    }

    public function odcs()
    {
        return $this->hasMany(Odc::class);
    }

    public function odps()
    {
        return $this->hasMany(Odp::class);
    }
}
