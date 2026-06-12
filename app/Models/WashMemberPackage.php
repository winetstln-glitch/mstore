<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WashMemberPackage extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'duration_days',
        'discount_percent',
        'benefits',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'discount_percent' => 'decimal:2',
        'benefits' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(WashMemberSubscription::class);
    }
}
