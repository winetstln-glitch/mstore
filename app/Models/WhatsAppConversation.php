<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WhatsAppConversation extends Model
{
    protected $fillable = [
        'phone_number',
        'conversation_id',
        'status',
        'assigned_cs_id',
        'assigned_at',
        'takeover_reason',
        'ai_history',
        'last_message_at',
        'unread_count',
    ];

    protected $casts = [
        'ai_history' => 'array',
        'assigned_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    /**
     * Get or create conversation by phone
     */
    public static function getOrCreate(string $phone): self
    {
        $conversation = self::where('phone_number', $phone)
            ->latest()
            ->first();

        if ($conversation && $conversation->status !== 'closed') {
            return $conversation;
        }

        return self::create([
            'phone_number' => $phone,
            'conversation_id' => (string) Str::uuid(),
            'status' => 'bot',
        ]);
    }

    /**
     * Get assigned CS
     */
    public function assignedCs(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_cs_id');
    }

    /**
     * Get all messages in this conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppLog::class, 'conversation_id', 'conversation_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Check if bot should reply
     */
    public function shouldBotReply(): bool
    {
        return $this->status === 'bot';
    }

    /**
     * Assign to CS
     */
    public function assignToCs(int $userId, string $reason = null): self
    {
        $this->update([
            'status' => 'assigned',
            'assigned_cs_id' => $userId,
            'assigned_at' => now(),
            'takeover_reason' => $reason,
        ]);

        return $this->fresh();
    }

    /**
     * Close conversation
     */
    public function close(): self
    {
        $this->update([
            'status' => 'closed',
            'unread_count' => 0,
        ]);

        return $this->fresh();
    }

    /**
     * Increment unread count
     */
    public function incrementUnread(): self
    {
        $this->increment('unread_count');
        $this->update(['last_message_at' => now()]);

        return $this->fresh();
    }

    /**
     * Reset unread count
     */
    public function resetUnread(): self
    {
        $this->update(['unread_count' => 0]);

        return $this->fresh();
    }
}
