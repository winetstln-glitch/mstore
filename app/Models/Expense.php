<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'company_branch_id',
        'business_unit_id', 'branch_id', 'profit_center_id', 'cost_center_id',
        'expense_number', 'expense_category_id', 'transaction_date',
        'total_amount', 'description', 'notes', 'status',
        'created_by', 'updated_by', 'metadata'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'transaction_date' => 'date',
        'metadata' => 'array',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($expense) {
            if (empty($expense->expense_number)) {
                $date = now()->format('Ymd');
                $count = static::whereDate('created_at', today())->count() + 1;
                $expense->expense_number = "EXP-{$date}-" . str_pad($count, 6, '0', STR_PAD_LEFT);
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ExpenseApproval::class)->orderBy('created_at', 'desc');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function generalTransactions()
    {
        return $this->morphMany(GeneralTransaction::class, 'reference');
    }
}
