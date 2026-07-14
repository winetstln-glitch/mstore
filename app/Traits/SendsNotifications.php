<?php

namespace App\Traits;

use App\Services\TelegramService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

trait SendsNotifications
{
    protected function sendGroupNotificationDetailed(string $message, string $category = 'ticket', array $channels = ['whatsapp', 'telegram']): array
    {
        $results = [
            'success' => false,
            'whatsapp' => [
                'attempted' => false,
                'success' => false,
                'message' => null,
            ],
            'telegram' => [
                'attempted' => false,
                'success' => false,
                'message' => null,
            ],
        ];

        if (in_array('whatsapp', $channels, true)) {
            $results['whatsapp']['attempted'] = true;
            $whatsAppService = app(WhatsAppService::class);

            try {
                $results['whatsapp']['success'] = $whatsAppService->sendGroupNotification($message, $category);

                if (! $results['whatsapp']['success']) {
                    $status = $whatsAppService->getGroupNotificationStatus($category);
                    $results['whatsapp']['message'] = $whatsAppService->getLastErrorMessage()
                        ?? $status['message']
                        ?? "Notifikasi WhatsApp {$category} gagal dikirim.";
                }
            } catch (\Exception $e) {
                $results['whatsapp']['message'] = $e->getMessage();
                Log::error("WhatsApp {$category} notification error: " . $e->getMessage());
            }
        }

        if (in_array('telegram', $channels, true)) {
            $results['telegram']['attempted'] = true;

            try {
                $results['telegram']['success'] = app(TelegramService::class)->sendGroupNotification($message, $category);

                if (! $results['telegram']['success']) {
                    $results['telegram']['message'] = "Notifikasi Telegram {$category} gagal dikirim.";
                }
            } catch (\Exception $e) {
                $results['telegram']['message'] = $e->getMessage();
                Log::error("Telegram {$category} notification error: " . $e->getMessage());
            }
        }

        $results['success'] = $results['whatsapp']['success'] || $results['telegram']['success'];

        return $results;
    }

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
        return $this->sendGroupNotificationDetailed($message, $category, $channels)['success'];
    }
}
