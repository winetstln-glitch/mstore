<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GeneralTransaction extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'company_branch_id',
        'business_unit_id', 'branch_id', 'profit_center_id', 'cost_center_id',
        'transaction_code', 'transaction_type', 'amount', 'currency', 'status',
        'description', 'reference_type', 'reference_id', 'created_by',
        'approved_by', 'approved_at', 'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'approved_at' => 'datetime',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_code)) {
                // Generate unique transaction code using timestamp and random string to avoid race conditions
                $date = now()->format('Ymd');
                $timestamp = now()->timestamp;
                $random = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $transaction->transaction_code = "GT-{$date}-{$timestamp}-{$random}";
            }
        });
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function profitCenter(): BelongsTo
    {
        return $this->belongsTo(ProfitCenter::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
