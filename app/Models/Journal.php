<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'company_branch_id',
        'journal_no', 'date', 'description', 'source_type', 'source_id', 'period_id', 'posted_by', 'posted_at', 'status'
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}
