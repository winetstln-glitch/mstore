<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessUnit extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'tax_id', 'address', 'phone', 'email', 'is_active', 'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function profitCenters(): HasMany
    {
        return $this->hasMany(ProfitCenter::class);
    }

    public function generalTransactions(): HasMany
    {
        return $this->hasMany(GeneralTransaction::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
