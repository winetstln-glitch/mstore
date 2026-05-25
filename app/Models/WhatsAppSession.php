<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class WhatsAppSession extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_sessions';

    protected $fillable = [
        'phone_number',
        'current_node',
        'payload',
        'step',
        'expires_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeByPhone($query, string $phoneNumber)
    {
        return $query->where('phone_number', $phoneNumber);
    }

    public static function getOrCreate(string $phoneNumber, int $ttlMinutes = 30): self
    {
        $session = self::byPhone($phoneNumber)->active()->first();

        if ($session) {
            $session->update([
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]);
            return $session;
        }

        return self::create([
            'phone_number' => $phoneNumber,
            'current_node' => null,
            'payload' => [],
            'step' => 0,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    public function updatePayload(array $data): self
    {
        $this->update([
            'payload' => array_merge($this->payload ?? [], $data),
            'expires_at' => now()->addMinutes(30),
        ]);
        return $this->fresh();
    }

    public function setCurrentNode(string $node): self
    {
        $this->update([
            'current_node' => $node,
            'step' => $this->step + 1,
            'expires_at' => now()->addMinutes(30),
        ]);
        return $this->fresh();
    }

    public function reset(): self
    {
        $this->update([
            'current_node' => null,
            'payload' => [],
            'step' => 0,
        ]);
        return $this->fresh();
    }
}
