<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WhatsAppConversation extends Model
{
    protected $table = 'whatsapp_conversations';

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
        'is_group',
        'group_id',
        'last_intent',
        'confidence_score',
        'sender_type',
    ];

    protected $casts = [
        'ai_history' => 'array',
        'assigned_at' => 'datetime',
        'last_message_at' => 'datetime',
        'is_group' => 'boolean',
    ];

    /**
     * Get or create conversation by phone with fail-safe
     */
    public static function getOrCreate(string $phone, bool $isGroup = false, ?string $groupId = null): ?self
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('whatsapp_conversations')) {
                \Illuminate\Support\Facades\Log::warning('whatsapp_conversations table not found, skipping conversation tracking');
                return null;
            }
            
            $query = self::where('phone_number', $phone);
            
            if ($isGroup && $groupId) {
                $query->where('group_id', $groupId);
            } else {
                $query->whereNull('group_id');
            }

            $conversation = $query->latest()->first();

            if ($conversation && $conversation->status !== 'closed') {
                return $conversation;
            }

            return self::create([
                'phone_number' => $phone,
                'conversation_id' => (string) Str::uuid(),
                'status' => 'bot',
                'is_group' => $isGroup,
                'group_id' => $groupId,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to get or create WhatsApp conversation: ' . $e->getMessage(), ['exception' => $e]);
            return null;
        }
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
