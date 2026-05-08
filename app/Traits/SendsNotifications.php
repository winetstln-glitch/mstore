<?php

namespace App\Traits;

use App\Services\TelegramService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

trait SendsNotifications
{
    /**
     * Send notification to WhatsApp and Telegram groups
     *
     * @param string $message
     * @param string $category 'ticket' or 'attendance'
     * @param array $channels Array of channels to send to: ['whatsapp', 'telegram']
     * @return bool True if at least one notification was sent successfully
     */
    protected function sendGroupNotification(string $message, string $category = 'ticket', array $channels = ['whatsapp', 'telegram'])
    {
        $sent = false;

        if (in_array('whatsapp', $channels)) {
            try {
                if (app(WhatsAppService::class)->sendGroupNotification($message, $category)) {
                    $sent = true;
                }
            } catch (\Exception $e) {
                Log::error("WhatsApp {$category} notification error: " . $e->getMessage());
            }
        }

        if (in_array('telegram', $channels)) {
            try {
                if (app(TelegramService::class)->sendGroupNotification($message, $category)) {
                    $sent = true;
                }
            } catch (\Exception $e) {
                Log::error("Telegram {$category} notification error: " . $e->getMessage());
            }
        }

        return $sent;
    }
}
