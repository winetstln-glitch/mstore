<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number',
        'subject',
        'customer_id',
        'technician_id',
        'type',
        'priority',
        'status',
        'description',
        'photo_before',
        'photo_proof',
        'location',
        'odp_id',
        'coordinator_id',
        'sla_deadline',
        'closed_at',
    ];

    protected $casts = [
        'sla_deadline' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Coordinator::class);
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_user');
    }

    // Helper for backward compatibility (optional, returns first tech)
    public function getTechnicianAttribute()
    {
        return $this->technicians->first();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TicketLog::class);
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'TKT-' . Str::upper(Str::random(6));
        } while (self::where('ticket_number', $number)->exists());
        return $number;
    }
}
