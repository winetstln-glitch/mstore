<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Odp extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'odc_id',
        'color',
        'latitude',
        'longitude',
        'capacity',
        'filled',
        'description',
        'region_id',
        'kampung',
    ];

    /**
     * Get the region that owns the ODP.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function odc()
    {
        return $this->belongsTo(Odc::class);
    }

    /**
     * Get the customers connected to this ODP.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function htbs()
    {
        return $this->hasMany(Htb::class);
    }
    
    public function isFull(): bool
    {
        if ($this->capacity === null) {
            return false;
        }
        return $this->filled >= $this->capacity;
    }
}
