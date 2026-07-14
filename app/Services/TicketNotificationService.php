<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Support\Facades\Log;

class TicketNotificationService
{
    protected $whatsAppService;
    protected $telegramService;

    public function __construct(WhatsAppService $whatsAppService, TelegramService $telegramService)
    {
        $this->whatsAppService = $whatsAppService;
        $this->telegramService = $telegramService;
    }

    /**
     * Build notification message from template
     */
    public function buildMessage(string $templateKey, string $channel, array $data): string
    {
        $settingKey = $templateKey . '_' . $channel . '_template';
        $template = (string) Setting::getValue($settingKey, '');

        // Fallback templates if setting not found
        if (empty($template)) {
            $template = $this->getFallbackTemplate($templateKey, $channel);
        }

        // Escape data for Telegram if needed
        if ($channel === 'telegram') {
            foreach ($data as $key => $value) {
                $data[$key] = TelegramService::escape((string) $value);
            }
        }

        // Replace placeholders
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) $value, $template);
            // Also handle singular/plural variations for technician name
            if ($key === 'technician_names') {
                $template = str_replace('{technician_name}', (string) $value, $template);
            }
            if ($key === 'technician_name') {
                $template = str_replace('{technician_names}', (string) $value, $template);
            }
        }

        return $template;
    }

    /**
     * Get fallback template
     */
    protected function getFallbackTemplate(string $templateKey, string $channel): string
    {
        $fallbacks = [
            'ticket_created' => [
                'whatsapp' => "🎫 *TIKET BARU: {ticket_number}*\n\n" .
                    "📌 *Tipe:* {ticket_type}\n" .
                    "👤 *Pelanggan:* {customer_name}\n" .
                    "👷 *Teknisi:* {technician_names}\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "⚡ *Prioritas:* {ticket_priority}\n" .
                    "📍 *Alamat:* {ticket_address}\n\n" .
                    "🔗 *Detail:* {ticket_url}\n\n" .
                    "🚀 _Sistem M-Store_",
                'telegram' => "🎫 <b>TIKET BARU: {ticket_number}</b>\n\n" .
                    "📌 <b>Tipe:</b> {ticket_type}\n" .
                    "👤 <b>Pelanggan:</b> {customer_name}\n" .
                    "👷 <b>Teknisi:</b> {technician_names}\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "⚡ <b>Prioritas:</b> {ticket_priority}\n" .
                    "📍 <b>Alamat:</b> {ticket_address}\n\n" .
                    "🔗 <b>Detail:</b> {ticket_url}\n\n" .
                    "🚀 <i>Sistem M-Store</i>",
            ],
            'ticket_status_updated' => [
                'whatsapp' => "🔄 *STATUS TIKET DIPERBARUI*\n\n" .
                    "🎫 *No Tiket:* {ticket_number}\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "🔄 *Status Baru:* {new_status}\n" .
                    "👤 *Diperbarui Oleh:* {updated_by}\n\n" .
                    "🔗 *Detail:* {ticket_url}",
                'telegram' => "🔄 <b>STATUS TIKET DIPERBARUI</b>\n\n" .
                    "🎫 <b>No Tiket:</b> {ticket_number}\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "🔄 <b>Status Baru:</b> {new_status}\n" .
                    "👤 <b>Diperbarui Oleh:</b> {updated_by}\n\n" .
                    "🔗 <b>Detail:</b> {ticket_url}",
            ],
            'ticket_solved' => [
                'whatsapp' => "✅ *TIKET SELESAI: {ticket_number}*\n\n" .
                    "👤 *Pelanggan:* {customer_name}\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "🛠️ *Oleh:* {updated_by}\n" .
                    "🗒️ *Hasil:* {ticket_note}\n\n" .
                    "🚀 _Sistem M-Store_",
                'telegram' => "✅ <b>TIKET SELESAI: {ticket_number}</b>\n\n" .
                    "👤 <b>Pelanggan:</b> {customer_name}\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "🛠️ <b>Oleh:</b> {updated_by}\n" .
                    "🗒️ <b>Hasil:</b> {ticket_note}\n\n" .
                    "🚀 <i>Sistem M-Store</i>",
            ],
            'ticket_assigned_group' => [
                'whatsapp' => "🎫 *PENUGASAN TIKET: {ticket_number}*\n\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "👷 *Teknisi:* {technician_names}\n" .
                    "👤 *Oleh:* {updated_by}\n" .
                    "🔗 *Detail:* {ticket_url}\n\n" .
                    "🚀 _Sistem M-Store_",
                'telegram' => "🎫 <b>PENUGASAN TIKET: {ticket_number}</b>\n\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "👷 <b>Teknisi:</b> {technician_names}\n" .
                    "👤 <b>Oleh:</b> {updated_by}\n" .
                    "🔗 <b>Detail:</b> {ticket_url}\n\n" .
                    "🚀 <i>Sistem M-Store</i>",
            ],
        ];

        return $fallbacks[$templateKey][$channel] ?? '';
    }

    /**
     * Check if channel is enabled for category
     */
    protected function isChannelEnabled(string $channel, string $category): bool
    {
        $settingKey = "{$channel}_{$category}_notification_enabled";
        return Setting::getValue($settingKey, '1') === '1';
    }

    /**
     * Send ticket created notification to groups
     */
    public function sendTicketCreatedNotification(Ticket $ticket, array $data): array
    {
        $whatsAppMessage = $this->buildMessage('ticket_created', 'whatsapp', $data);
        $telegramMessage = $this->buildMessage('ticket_created', 'telegram', $data);

        $channels = [];
        if ($this->isChannelEnabled('whatsapp', 'ticket')) {
            $channels[] = 'whatsapp';
        }
        if ($this->isChannelEnabled('telegram', 'ticket')) {
            $channels[] = 'telegram';
        }

        if (count($channels) === 0) {
            return ['success' => true, 'whatsapp' => ['attempted' => false, 'success' => true], 'telegram' => ['attempted' => false, 'success' => true]];
        }

        if (count($channels) === 1) {
            if ($channels[0] === 'whatsapp') {
                return $this->sendGroupNotification($whatsAppMessage, 'ticket', ['whatsapp']);
            }
            return $this->sendGroupNotification($telegramMessage, 'ticket', ['telegram']);
        }

        return $this->sendGroupNotificationWithDifferentMessages(
            $whatsAppMessage,
            $telegramMessage,
            'ticket'
        );
    }

    /**
     * Send ticket status updated notification to groups
     */
    public function sendTicketStatusUpdatedNotification(Ticket $ticket, array $data, array $channels = ['whatsapp', 'telegram']): array
    {
        $whatsAppMessage = $this->buildMessage('ticket_status_updated', 'whatsapp', $data);
        $telegramMessage = $this->buildMessage('ticket_status_updated', 'telegram', $data);

        $enabledChannels = [];
        if (in_array('whatsapp', $channels, true) && $this->isChannelEnabled('whatsapp', 'ticket')) {
            $enabledChannels[] = 'whatsapp';
        }
        if (in_array('telegram', $channels, true) && $this->isChannelEnabled('telegram', 'ticket')) {
            $enabledChannels[] = 'telegram';
        }

        if (count($enabledChannels) === 0) {
            return ['success' => true, 'whatsapp' => ['attempted' => false, 'success' => true], 'telegram' => ['attempted' => false, 'success' => true]];
        }

        if (count($enabledChannels) === 1) {
            if ($enabledChannels[0] === 'whatsapp') {
                return $this->sendGroupNotification($whatsAppMessage, 'ticket', ['whatsapp']);
            }
            return $this->sendGroupNotification($telegramMessage, 'ticket', ['telegram']);
        }

        return $this->sendGroupNotificationWithDifferentMessages(
            $whatsAppMessage,
            $telegramMessage,
            'ticket'
        );
    }

    /**
     * Send ticket solved notification to groups
     */
    public function sendTicketSolvedNotification(Ticket $ticket, array $data): array
    {
        $whatsAppMessage = $this->buildMessage('ticket_solved', 'whatsapp', $data);
        $telegramMessage = $this->buildMessage('ticket_solved', 'telegram', $data);

        $channels = [];
        if ($this->isChannelEnabled('whatsapp', 'ticket')) {
            $channels[] = 'whatsapp';
        }
        if ($this->isChannelEnabled('telegram', 'ticket')) {
            $channels[] = 'telegram';
        }

        if (count($channels) === 0) {
            return ['success' => true, 'whatsapp' => ['attempted' => false, 'success' => true], 'telegram' => ['attempted' => false, 'success' => true]];
        }

        if (count($channels) === 1) {
            if ($channels[0] === 'whatsapp') {
                return $this->sendGroupNotification($whatsAppMessage, 'ticket', ['whatsapp']);
            }
            return $this->sendGroupNotification($telegramMessage, 'ticket', ['telegram']);
        }

        return $this->sendGroupNotificationWithDifferentMessages(
            $whatsAppMessage,
            $telegramMessage,
            'ticket'
        );
    }

    /**
     * Send ticket assigned notification to group
     */
    public function sendTicketAssignedToGroupNotification(Ticket $ticket, array $data): array
    {
        $whatsAppMessage = $this->buildMessage('ticket_assigned_group', 'whatsapp', $data);
        $telegramMessage = $this->buildMessage('ticket_assigned_group', 'telegram', $data);

        $channels = [];
        if ($this->isChannelEnabled('whatsapp', 'ticket')) {
            $channels[] = 'whatsapp';
        }
        if ($this->isChannelEnabled('telegram', 'ticket')) {
            $channels[] = 'telegram';
        }

        if (count($channels) === 0) {
            return ['success' => true, 'whatsapp' => ['attempted' => false, 'success' => true], 'telegram' => ['attempted' => false, 'success' => true]];
        }

        if (count($channels) === 1) {
            if ($channels[0] === 'whatsapp') {
                return $this->sendGroupNotification($whatsAppMessage, 'ticket', ['whatsapp']);
            }
            return $this->sendGroupNotification($telegramMessage, 'ticket', ['telegram']);
        }

        return $this->sendGroupNotificationWithDifferentMessages(
            $whatsAppMessage,
            $telegramMessage,
            'ticket'
        );
    }

    /**
     * Send ticket assigned notification to technicians
     */
    public function sendTicketAssignedToTechnicians(Ticket $ticket, array $technicianIds): void
    {
        foreach ($technicianIds as $techId) {
            $tech = User::find($techId);
            if ($tech) {
                $tech->notify(new TicketAssignedNotification($ticket));
            }
        }
    }

    /**
     * Send group notification with the same message to all channels
     */
    public function sendGroupNotification(string $message, string $category = 'ticket', array $channels = ['whatsapp', 'telegram']): array
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

        if (in_array('whatsapp', $channels, true) && $this->isChannelEnabled('whatsapp', $category)) {
            $results['whatsapp']['attempted'] = true;
            try {
                $results['whatsapp']['success'] = $this->whatsAppService->sendGroupNotification($message, $category);
                if (! $results['whatsapp']['success']) {
                    $status = $this->whatsAppService->getGroupNotificationStatus($category);
                    $results['whatsapp']['message'] = $this->whatsAppService->getLastErrorMessage()
                        ?? $status['message']
                        ?? "Notifikasi WhatsApp {$category} gagal dikirim.";
                }
            } catch (\Exception $e) {
                $results['whatsapp']['message'] = $e->getMessage();
                Log::error("WhatsApp {$category} notification error: " . $e->getMessage());
            }
        } else {
            $results['whatsapp']['success'] = true;
        }

        if (in_array('telegram', $channels, true) && $this->isChannelEnabled('telegram', $category)) {
            $results['telegram']['attempted'] = true;
            try {
                $results['telegram']['success'] = $this->telegramService->sendGroupNotification($message, $category);
                if (! $results['telegram']['success']) {
                    $results['telegram']['message'] = "Notifikasi Telegram {$category} gagal dikirim.";
                }
            } catch (\Exception $e) {
                $results['telegram']['message'] = $e->getMessage();
                Log::error("Telegram {$category} notification error: " . $e->getMessage());
            }
        } else {
            $results['telegram']['success'] = true;
        }

        $results['success'] = $results['whatsapp']['success'] || $results['telegram']['success'];
        return $results;
    }

    /**
     * Send group notification with different messages for WhatsApp and Telegram
     */
    public function sendGroupNotificationWithDifferentMessages(string $whatsAppMessage, string $telegramMessage, string $category = 'ticket'): array
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

        if ($this->isChannelEnabled('whatsapp', $category)) {
            $results['whatsapp']['attempted'] = true;
            try {
                $results['whatsapp']['success'] = $this->whatsAppService->sendGroupNotification($whatsAppMessage, $category);
                if (! $results['whatsapp']['success']) {
                    $status = $this->whatsAppService->getGroupNotificationStatus($category);
                    $results['whatsapp']['message'] = $this->whatsAppService->getLastErrorMessage()
                        ?? $status['message']
                        ?? "Notifikasi WhatsApp {$category} gagal dikirim.";
                }
            } catch (\Exception $e) {
                $results['whatsapp']['message'] = $e->getMessage();
                Log::error("WhatsApp {$category} notification error: " . $e->getMessage());
            }
        } else {
            $results['whatsapp']['success'] = true;
        }

        if ($this->isChannelEnabled('telegram', $category)) {
            $results['telegram']['attempted'] = true;
            try {
                $results['telegram']['success'] = $this->telegramService->sendGroupNotification($telegramMessage, $category);
                if (! $results['telegram']['success']) {
                    $results['telegram']['message'] = "Notifikasi Telegram {$category} gagal dikirim.";
                }
            } catch (\Exception $e) {
                $results['telegram']['message'] = $e->getMessage();
                Log::error("Telegram {$category} notification error: " . $e->getMessage());
            }
        } else {
            $results['telegram']['success'] = true;
        }

        $results['success'] = $results['whatsapp']['success'] || $results['telegram']['success'];
        return $results;
    }

    /**
     * Collect WhatsApp notification warnings
     */
    public function collectWhatsAppNotificationWarning(array $notificationResult, array &$warnings): void
    {
        $whatsAppResult = $notificationResult['whatsapp'] ?? null;
        if (! is_array($whatsAppResult) || ! ($whatsAppResult['attempted'] ?? false) || ($whatsAppResult['success'] ?? false)) {
            return;
        }

        $message = trim((string) ($whatsAppResult['message'] ?? ''));
        if ($message !== '' && ! in_array($message, $warnings, true)) {
            $warnings[] = $message;
        }
    }

    /**
     * Redirect with notification warning
     */
    public function redirectWithNotificationWarning($redirectResponse, array $warnings)
    {
        if ($warnings === []) {
            return $redirectResponse;
        }

        return $redirectResponse->with('warning', implode(' ', array_unique($warnings)));
    }
}
