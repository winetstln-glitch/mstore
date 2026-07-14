<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YearlySummary extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'company_branch_id',
        'business_unit_id', 'branch_id', 'profit_center_id', 'cost_center_id',
        'year', 'total_income', 'total_expense', 'total_profit',
        'total_transactions', 'total_customers_served', 'metadata'
    ];

    protected $casts = [
        'total_income' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'metadata' => 'array',
    ];

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
}