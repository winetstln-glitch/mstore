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
     * @return void
     */
    protected function sendGroupNotification(string $message, string $category = 'ticket')
    {
        try {
            app(WhatsAppService::class)->sendGroupNotification($message, $category);
        } catch (\Exception $e) {
            Log::error("WhatsApp {$category} notification error: " . $e->getMessage());
        }

        try {
            app(TelegramService::class)->sendGroupNotification($message, $category);
        } catch (\Exception $e) {
            Log::error("Telegram {$category} notification error: " . $e->getMessage());
        }
    }
}
