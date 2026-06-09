<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashMemberLevel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'min_transactions',
        'max_transactions',
        'discount_percent',
        'priority_rank',
        'benefits',
        'is_active',
    ];

    protected $casts = [
        'benefits' => 'array',
        'is_active' => 'boolean',
        'discount_percent' => 'float',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(WashMember::class);
    }
}

