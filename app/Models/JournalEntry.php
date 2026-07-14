<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntry extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'company_branch_id',
        'journal_id', 'account_id', 'debit', 'credit', 'memo', 'unit', 'cost_center', 'reversal_of_id'
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \Exception('Journal entries are immutable - cannot update');
        });

        static::deleting(function () {
            throw new \Exception('Journal entries are immutable - cannot delete');
        });
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_of_id');
    }
}
