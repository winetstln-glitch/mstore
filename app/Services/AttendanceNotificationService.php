<?php

namespace App\Services;

use App\Models\TechnicianAttendance;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AttendanceNotificationService
{
    public function sendSingleAttendanceNotification(User $admin, User $targetUser, TechnicianAttendance $attendance): array
    {
        $sentCount = 0;
        $channels = [];
        $clockIn = $attendance->clock_in?->format('H:i') ?? '-';
        $clockOut = $attendance->clock_out?->format('H:i') ?? '-';
        $status = ucfirst($attendance->status);
        $date = $attendance->clock_in?->translatedFormat('d F Y') ?? '-';

        $whatsAppMessage = "Halo {$targetUser->name},\n\nBerikut detail absensi Anda:\n📅 Tanggal: {$date}\n⏰ Masuk: {$clockIn}\n⏰ Pulang: {$clockOut}\n📊 Status: {$status}\n\nTerima kasih.";

        try {
            if ($targetUser->phone) {
                $wa = new WhatsAppService();
                $wa->sendMessage($targetUser->phone, $whatsAppMessage, 'attendance_notification');
                $sentCount++;
                $channels[] = 'WhatsApp';
            }
        } catch (\Throwable $e) {
            Log::error('WhatsApp notification failed', [
                'error' => $e->getMessage(),
                'user_id' => $targetUser->id,
            ]);
        }

        try {
            if ($targetUser->telegram_chat_id) {
                $telegram = new TelegramService();
                $tgMessage = "🔔 *DETAIL ABSENSI*\n\n" .
                    "Halo *" . TelegramService::escape($targetUser->name) . "*,\n\n" .
                    "Berikut detail absensi Anda:\n" .
                    "📅 *Tanggal:* " . TelegramService::escape($date) . "\n" .
                    "⏰ *Masuk:* " . TelegramService::escape($clockIn) . "\n" .
                    "⏰ *Pulang:* " . TelegramService::escape($clockOut) . "\n" .
                    "📊 *Status:* *" . TelegramService::escape($status) . "*\n\n" .
                    "Terima kasih.";

                $telegram->sendMessage($targetUser->telegram_chat_id, $tgMessage);
                $sentCount++;
                $channels[] = 'Telegram';
            }
        } catch (\Throwable $e) {
            Log::error('Telegram notification failed', [
                'error' => $e->getMessage(),
                'user_id' => $targetUser->id,
            ]);
        }

        return ['sent' => $sentCount > 0, 'channels' => $channels];
    }
}
