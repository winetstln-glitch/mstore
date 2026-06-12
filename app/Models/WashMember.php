<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WashMember extends Model
{
    protected $fillable = [
        'member_number',
        'name',
        'whatsapp',
        'email',
        'address',
        'joined_at',
        'wash_member_level_id',
        'total_transactions',
        'total_visits',
        'total_spending',
        'status',
        'last_transaction_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_transaction_at' => 'datetime',
        'total_spending' => 'float',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(WashMemberLevel::class, 'wash_member_level_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(WashMemberVehicle::class, 'wash_member_id');
    }

    public function card(): HasOne
    {
        return $this->hasOne(WashMemberCard::class, 'wash_member_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WashTransaction::class, 'wash_member_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(WashMemberSubscription::class, 'wash_member_id');
    }

    public function activeSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->latest()
            ->first();
    }
}
