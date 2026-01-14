<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investor extends Model
{
    use HasFactory;

    protected $fillable = [
        'coordinator_id',
        'name',
        'phone',
        'description',
    ];

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Coordinator::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function incomeTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->where('type', 'income');
    }

    public function expenseTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->where('type', 'expense');
    }
}
