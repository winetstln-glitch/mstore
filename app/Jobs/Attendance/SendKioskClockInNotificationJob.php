<?php

namespace App\Jobs\Attendance;

use App\Models\TechnicianAttendance;
use App\Models\User;
use App\Services\TelegramService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendKioskClockInNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public TechnicianAttendance $attendance
    ) {}

    public function handle(): void
    {
        try {
            $statusLabel = match($this->attendance->status) {
                'present' => 'HADIR ✅',
                'late' => 'TERLAMBAT ⚠️',
                default => strtoupper($this->attendance->status)
            };
            $time = $this->attendance->clock_in?->format('H:i') ?? '-';
            $date = $this->attendance->clock_in?->translatedFormat('d M Y') ?? '-';
            
            $waMessage = "🔔 *NOTIFIKASI ABSEN MASUK (KIOSK)*\n\n" .
                         "👤 *Nama:* {$this->user->name}\n" .
                         "⏰ *Jam:* {$time} WIB\n" .
                         "📅 *Tanggal:* {$date}\n" .
                         "📊 *Status:* {$statusLabel}\n" .
                         "📝 *Metode:* Kiosk Scan\n\n" .
                         "🚀 _Sistem M-Store_";
            
            $tgMessage = "🔔 *NOTIFIKASI ABSEN MASUK (KIOSK)*\n\n" .
                         "👤 *Nama:* " . TelegramService::escape($this->user->name) . "\n" .
                         "⏰ *Jam:* {$time} WIB\n" .
                         "📅 *Tanggal:* {$date}\n" .
                         "📊 *Status:* {$statusLabel}\n" .
                         "📝 *Metode:* Kiosk Scan\n\n" .
                         "🚀 _Sistem M-Store_";
            
            app(WhatsAppService::class)->sendGroupNotification($waMessage, 'attendance');
            app(TelegramService::class)->sendGroupNotification($tgMessage, 'attendance');
        } catch (\Throwable $e) {
            Log::error('Kiosk Clock In Notification Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->user->id,
                'attendance_id' => $this->attendance->id,
            ]);
        }
    }
}
