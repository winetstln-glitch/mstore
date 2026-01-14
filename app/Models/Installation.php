<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installation extends Model
{
    protected $fillable = [
        'customer_id',
        'technician_id',
        'status',
        'plan_date',
        'notes',
        'coordinates',
        'photo_before',
        'photo_after',
    ];

    protected $casts = [
        'plan_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
