<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function odp()
    {
        return $this->belongsTo(Odp::class);
    }

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function installations()
    {
        return $this->hasMany(Installation::class);
    }
}
