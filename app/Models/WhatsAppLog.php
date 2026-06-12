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
        'ai_history_id',
        'user_id',
        'customer_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'processing_time_ms' => 'integer',
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
        if (isset($extraData['conversation_id'])) {
            $data['conversation_id'] = $extraData['conversation_id'];
        }
        if (isset($extraData['sender_type'])) {
            $data['sender_type'] = $extraData['sender_type'];
        }
        if (isset($extraData['message_type'])) {
            $data['message_type'] = $extraData['message_type'];
        }
        if (isset($extraData['processing_time_ms'])) {
            $data['processing_time_ms'] = $extraData['processing_time_ms'];
        }
        if (isset($extraData['user_id'])) {
            $data['user_id'] = $extraData['user_id'];
        }
        if (isset($extraData['customer_id'])) {
            $data['customer_id'] = $extraData['customer_id'];
        }

        return self::create($data);
    }
}
