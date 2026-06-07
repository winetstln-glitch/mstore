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
