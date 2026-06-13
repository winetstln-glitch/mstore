<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppLog extends Model
{
    protected $table = 'whatsapp_logs';

    protected $fillable = [
        'type',
        'phone_number',
        'message',
        'status',
        'provider_message_id',
        'payload',
        'error_message',
        'conversation_id',
        'sender_type',
        'message_type',
        'processing_time_ms',
        'duplicate_count',
        'duplicate_detected_at',
        'ai_confidence',
        'detected_intent',
        'ai_history_id',
        'user_id',
        'customer_id',
        'is_group',
        'group_id',
        'intent',
        'confidence_score',
    ];

    protected $casts = [
        'payload' => 'array',
        'processing_time_ms' => 'integer',
        'is_group' => 'boolean',
    ];

    protected function phoneMasked(): Attribute
    {
        return Attribute::make(
            get: function () {
                $p = preg_replace('/\s+/', '', (string) ($this->phone_number ?? ''));
                if ($p === '') {
                    return '';
                }
                if (strlen($p) <= 4) {
                    return str_repeat('*', strlen($p));
                }
                return str_repeat('*', max(0, strlen($p) - 4)) . substr($p, -4);
            }
        );
    }

    // Scope for incoming messages
    public function scopeIncoming($query)
    {
        return $query->where('type', 'incoming');
    }

    // Scope for outgoing messages
    public function scopeOutgoing($query)
    {
        return $query->where('type', 'outgoing');
    }

    // Scope for failed messages
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Relasi ke User (CS yang mengirim pesan)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Customer
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Helper method to create log
    public static function logMessage($type, $phoneNumber, $message, $status = 'pending', $payload = null, $errorMessage = null, $extraData = [])
    {
        if (is_array($payload) && ! app()->environment('local')) {
            $payload = [
                'keys' => array_slice(array_keys($payload), 0, 50),
            ];
        }

        $data = [
            'type' => $type ?? 'unknown',
            'phone_number' => $phoneNumber ?? 'unknown',
            'message' => $message ?? '',
            'status' => $status ?? 'pending',
            'provider_message_id' => null,
            'payload' => $payload,
            'error_message' => $errorMessage,
        ];

        // Merge extra data
        $extraFields = [
            'conversation_id',
            'sender_type',
            'message_type',
            'processing_time_ms',
            'provider_message_id',
            'ai_confidence',
            'detected_intent',
            'ai_history_id',
            'user_id',
            'customer_id',
            'is_group',
            'group_id',
            'intent',
            'confidence_score'
        ];

        foreach ($extraFields as $field) {
            if (isset($extraData[$field])) {
                $data[$field] = $extraData[$field];
            }
        }

        return self::create($data);
    }
}
