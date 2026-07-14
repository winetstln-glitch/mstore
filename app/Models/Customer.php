<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'path' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function odp()
    {
        return $this->belongsTo(Odp::class);
    }

    public function htb()
    {
        return $this->belongsTo(Htb::class);
    }

    public function networkDiagnostics()
    {
        return $this->hasMany(NetworkDiagnostic::class);
    }

    public function olt()
    {
        return $this->belongsTo(OLT::class);
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

    public function genieStatus()
    {
        return $this->hasOne(GenieDeviceStatus::class);
    }

    public function invoicesByUser()
    {
        return $this->hasMany(Invoice::class, 'user_id', 'user_id');
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}
