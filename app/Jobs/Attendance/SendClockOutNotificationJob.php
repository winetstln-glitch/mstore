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

class SendClockOutNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public TechnicianAttendance $attendance
    ) {}

    public function handle(): void
    {
        try {
            $time = $this->attendance->clock_out?->format('H:i') ?? '-';
            $date = $this->attendance->clock_out?->translatedFormat('d M Y') ?? '-';
            $clockOutNotes = '-';
            if ($this->attendance->notes) {
                $parts = explode("\nClock Out Note: ", $this->attendance->notes);
                $clockOutNotes = $parts[1] ?? '-';
            }
            
            $waMessage = "🔔 *NOTIFIKASI ABSEN PULANG*\n\n" .
                         "👤 *Nama:* {$this->user->name}\n" .
                         "⏰ *Jam:* {$time} WIB\n" .
                         "📅 *Tanggal:* {$date}\n" .
                         "🏁 *Status:* SELESAI TUGAS 👋\n" .
                         "📝 *Catatan:* {$clockOutNotes}\n\n" .
                         "🚀 _Sistem M-Store_";
            
            $tgMessage = "🔔 *NOTIFIKASI ABSEN PULANG*\n\n" .
                         "👤 *Nama:* " . TelegramService::escape($this->user->name) . "\n" .
                         "⏰ *Jam:* {$time} WIB\n" .
                         "📅 *Tanggal:* {$date}\n" .
                         "🏁 *Status:* SELESAI TUGAS 👋\n" .
                         "📝 *Catatan:* " . TelegramService::escape($clockOutNotes) . "\n\n" .
                         "🚀 _Sistem M-Store_";
            
            app(WhatsAppService::class)->sendGroupNotification($waMessage, 'attendance');
            app(TelegramService::class)->sendGroupNotification($tgMessage, 'attendance');
        } catch (\Throwable $e) {
            Log::error('Attendance Clock Out Notification Error', [
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
