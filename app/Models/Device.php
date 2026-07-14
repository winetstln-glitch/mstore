<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'brand',
        'model',
        'sn',
        'olt_port',
        'vlan',
        'status',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
