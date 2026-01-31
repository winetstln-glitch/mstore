<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Closure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'capacity',
        'pon_port',
        'cable_no',
        'area',
        'coordinates',
        'parent_type',
        'parent_id',
        'description',
        'region_id',
    ];

    public function parent()
    {
        return $this->morphTo();
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
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
