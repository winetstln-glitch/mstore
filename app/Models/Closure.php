<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Closure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'odc_id',
        'region_id',
        'latitude',
        'longitude',
        'capacity',
        'filled',
        'description',
        'image',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function odc()
    {
        return $this->belongsTo(Odc::class);
    }

    public function isFull(): bool
    {
        if ($this->capacity === null) {
            return false;
        }
        return $this->filled >= $this->capacity;
    }
}
