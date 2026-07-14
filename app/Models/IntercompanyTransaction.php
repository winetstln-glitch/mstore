<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IntercompanyTransaction extends Model
{
    protected $fillable = [
        'transaction_code',
        'from_company_id',
        'to_company_id',
        'source_type',
        'source_id',
        'amount',
        'currency',
        'status',
        'elimination_status',
        'description',
        'from_journal_id',
        'to_journal_id',
        'settled_at',
    ];

    protected $casts = [
        'settled_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function fromCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'from_company_id');
    }

    public function toCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'to_company_id');
    }

    public function fromJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'from_journal_id');
    }

    public function toJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'to_journal_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public static function generateTransactionCode(): string
    {
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;
        return "ICT-{$date}-" . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
}