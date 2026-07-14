<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtkCashRegister extends Model
{
    protected $fillable = [
        'user_id', 'name', 'opening_balance', 'closing_balance', 'status', 'opened_at', 'closed_at',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AtkCashMovement::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AtkTransaction::class);
    }
}
