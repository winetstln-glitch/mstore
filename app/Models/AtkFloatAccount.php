<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtkFloatAccount extends Model
{
    protected $fillable = [
        'code', 'name', 'account_type', 'current_balance', 'status', 'description',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(AtkFloatTransaction::class);
    }
}
