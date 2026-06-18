<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptTemplate extends Model
{
    protected $fillable = [
        'name',
        'transaction_type',
        'size',
        'orientation',
        'header',
        'footer',
        'show_logo',
        'show_qr',
        'show_barcode',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'show_logo' => 'boolean',
        'show_qr' => 'boolean',
        'show_barcode' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function getActiveForType(string $transactionType): ?self
    {
        return self::where('transaction_type', $transactionType)
            ->where('is_active', true)
            ->first();
    }
}
