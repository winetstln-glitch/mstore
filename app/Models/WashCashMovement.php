<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WashCashMovement extends Model
{
    use Auditable;

    protected $fillable = [
        'wash_cash_register_id',
        'user_id',
        'wash_shift_session_id',
        'type',
        'amount',
        'reference_no',
        'description',
        'movement_date'
    ];

    protected $casts = [
        'movement_date' => 'datetime',
    ];

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(WashCashRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shiftSession(): BelongsTo
    {
        return $this->belongsTo(WashShiftSession::class);
    }
}
