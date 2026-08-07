<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WashCommissionEarning extends Model
{
    use HasFactory;

    public const STATUS_EARNED = 'earned';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'wash_employee_id',
        'wash_transaction_item_id',
        'wash_transaction_id',
        'vehicle_type_snapshot',
        'size_tier_snapshot',
        'quantity',
        'rate_per_unit',
        'total_earned',
        'status',
        'paid_at',
        'paid_reference_type',
        'paid_reference_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'rate_per_unit' => 'integer',
        'total_earned' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(WashEmployee::class, 'wash_employee_id');
    }

    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(WashTransactionItem::class, 'wash_transaction_item_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WashTransaction::class, 'wash_transaction_id');
    }

    public function paidReference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeEarned($query)
    {
        return $query->where('status', self::STATUS_EARNED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeVoided($query)
    {
        return $query->where('status', self::STATUS_VOIDED);
    }
}
