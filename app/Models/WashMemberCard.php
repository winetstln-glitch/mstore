<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WashMemberCard extends Model
{
    protected $fillable = [
        'wash_member_id',
        'card_number',
        'verification_token',
        'issued_at',
        'expires_at',
        'status',
        'meta',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(WashMember::class, 'wash_member_id');
    }
}

