<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsolidationReport extends Model
{
    protected $fillable = [
        'report_type',
        'start_date',
        'end_date',
        'currency',
        'total_revenue',
        'total_expense',
        'intercompany_eliminations',
        'consolidated_profit',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_revenue' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'intercompany_eliminations' => 'decimal:2',
        'consolidated_profit' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ConsolidationItem::class);
    }
}