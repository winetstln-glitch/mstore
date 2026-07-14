<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeProfile extends Model
{
    protected $fillable = [
        'name',
        'transaction_type',
        'fee_mode',
        'custom_formula',
        'cost_price',
        'markup_value',
        'markup_type',
        'is_active',
        'allow_override',
        'module',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_override' => 'boolean',
        'cost_price' => 'decimal:2',
        'markup_value' => 'decimal:2',
    ];

    public function tiers(): HasMany
    {
        return $this->hasMany(FeeTier::class)->orderBy('sort_order');
    }

    public function feeLogs(): HasMany
    {
        return $this->hasMany(FeeLog::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function getActiveForType(string $transactionType, string $module = 'atk'): ?self
    {
        return self::where('transaction_type', $transactionType)
            ->where('module', $module)
            ->where('is_active', true)
            ->first();
    }
}
