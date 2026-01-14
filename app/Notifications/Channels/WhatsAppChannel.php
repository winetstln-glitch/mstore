<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);
        
        $phone = $notifiable->phone;

        if (empty($phone)) {
            Log::warning("WhatsAppChannel: No phone number for user {$notifiable->id}");
            return;
        }

        // Basic formatting: ensure it doesn't start with 0 if using 62, etc.
        // But let's trust the input or service for now, or just basic check.
        
        try {
            $this->whatsappService->sendMessage(
                $phone, 
                $message, 
                'ticket_assignment', 
                null // customer_id is null for technician
            );
        } catch (\Exception $e) {
            Log::error("WhatsAppChannel Error: " . $e->getMessage());
        }
    }
}
