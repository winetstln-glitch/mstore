<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashShiftSession extends Model
{
    use Auditable;

    protected $fillable = [
        'wash_shift_id',
        'user_id',
        'wash_cash_register_id',
        'opened_at',
        'closed_at',
        'opening_cash',
        'closing_cash',
        'total_sales',
        'total_expenses',
        'cash_difference',
        'notes',
        'status'
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(WashShift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(WashCashRegister::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WashTransaction::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(WashCashMovement::class);
    }
}
