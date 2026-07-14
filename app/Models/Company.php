<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name', 'code', 'tax_id', 'currency', 'country', 'address', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(CompanyBranch::class);
    }

    public function generalTransactions(): HasMany
    {
        return $this->hasMany(GeneralTransaction::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }
}