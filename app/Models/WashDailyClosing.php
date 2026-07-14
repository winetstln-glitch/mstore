<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WashDailyClosing extends Model
{
    use Auditable;

    protected $fillable = [
        'closing_date',
        'wash_revenue',
        'cafe_revenue',
        'total_expenses',
        'gross_profit',
        'net_profit',
        'total_member_transactions',
        'total_non_member_transactions',
        'closed_by',
        'approved_by',
        'approved_at',
        'status',
        'notes'
    ];

    protected $casts = [
        'closing_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
