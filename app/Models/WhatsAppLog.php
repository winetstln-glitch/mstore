<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
    ];

    protected $casts = [
        'payload' => 'array',
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

    // Helper method to create log
    public static function logMessage($type, $phoneNumber, $message, $status = 'pending', $payload = null, $errorMessage = null)
    {
        if (is_array($payload) && ! app()->environment('local')) {
            $payload = [
                'keys' => array_slice(array_keys($payload), 0, 50),
            ];
        }

        return self::create([
            'type' => $type ?? 'unknown',
            'phone_number' => $phoneNumber ?? 'unknown',
            'message' => $message ?? '',
            'status' => $status ?? 'pending',
            'provider_message_id' => null, // Set to null explicitly
            'payload' => $payload,
            'error_message' => $errorMessage,
        ]);
    }
}
