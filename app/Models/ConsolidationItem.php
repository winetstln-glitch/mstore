<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsolidationItem extends Model
{
    protected $fillable = [
        'consolidation_report_id',
        'company_id',
        'account_code',
        'account_name',
        'amount',
        'eliminated_amount',
        'consolidated_amount',
        'item_type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'eliminated_amount' => 'decimal:2',
        'consolidated_amount' => 'decimal:2',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ConsolidationReport::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}