<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $fillable = [
        'receipt_number',
        'transaction_type',
        'transaction_id',
        'receipt_template_id',
        'status',
        'verification_url',
        'qr_code_path',
        'barcode_path',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReceiptTemplate::class, 'receipt_template_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ReceiptActivityLog::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function transaction()
    {
        $modelClass = match ($this->transaction_type) {
            'pos', 'bank', 'cashout', 'topup', 'ppob', 'qris' => AtkTransaction::class,
            default => null,
        };

        return $modelClass ? $this->belongsTo($modelClass, 'transaction_id') : null;
    }

    public static function generateReceiptNumber(): string
    {
        $prefix = 'STR-ATK-';
        $date = now()->format('Ymd');
        $sequence = self::whereDate('created_at', now())->count() + 1;
        return $prefix . $date . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}
