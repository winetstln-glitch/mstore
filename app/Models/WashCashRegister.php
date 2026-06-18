<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashCashRegister extends Model
{
    use Auditable;

    protected $fillable = ['name', 'code', 'description', 'current_balance', 'is_active'];

    public function sessions(): HasMany
    {
        return $this->hasMany(WashShiftSession::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(WashCashMovement::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WashTransaction::class);
    }
}
