<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReconciliationReport extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'company_branch_id',
        'date', 'business_unit_id', 'total_transactions', 'total_journal_entries',
        'difference', 'status', 'details_json',
    ];

    protected $casts = [
        'date' => 'date',
        'difference' => 'decimal:2',
        'details_json' => 'array',
    ];

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsolidationItem::class);
    }
}