<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KasbonLoan extends Model
{
    protected $fillable = [
        'user_id',
        'principal_amount',
        'start_date',
        'tenor_months',
        'monthly_installment',
        'description',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'monthly_installment' => 'decimal:2',
            'start_date' => 'date',
            'tenor_months' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(KasbonInstallment::class)->latest('date');
    }

    public function getRemainingAttribute(): float
    {
        $paid = $this->installments()->sum('amount');
        return (float) $this->principal_amount - (float) $paid;
    }

    public function checkAndUpdateStatus(): void
    {
        if ($this->remaining <= 0 && $this->status !== 'closed') {
            $this->status = 'closed';
            $this->save();
        } elseif ($this->remaining > 0 && $this->status === 'closed') {
            $this->status = 'active';
            $this->save();
        }
    }
}
