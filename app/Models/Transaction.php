<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coordinator_id',
        'investor_id',
        'type',
        'category',
        'amount',
        'description',
        'transaction_date',
        'reference_number',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Coordinator::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }
}
