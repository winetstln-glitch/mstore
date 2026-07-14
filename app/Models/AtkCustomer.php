<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtkCustomer extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'address', 'description', 'total_spent', 'loyalty_points',
    ];

    protected $casts = [
        'total_spent' => 'decimal:2',
        'loyalty_points' => 'integer',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(AtkTransaction::class);
    }
}
