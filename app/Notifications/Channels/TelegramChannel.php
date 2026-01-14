<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toTelegram')) {
            return;
        }

        $message = $notification->toTelegram($notifiable);
        
        $chatId = $notifiable->telegram_chat_id;

        if (empty($chatId)) {
            Log::warning("TelegramChannel: No Chat ID for user {$notifiable->id} - {$notifiable->name}");
            return;
        }

        $this->telegramService->sendMessage($chatId, $message);
    }
}
