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

class SendManualAttendanceNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public TechnicianAttendance $attendance,
        public User $adminUser
    ) {}

    public function handle(): void
    {
        try {
            $statusLabel = match($this->attendance->status) {
                'present' => 'HADIR ✅',
                'late' => 'TERLAMBAT ⚠️',
                'leave' => 'CUTI 🌴',
                'permit' => 'IZIN 📝',
                'sick' => 'SAKIT 🤒',
                'alpha' => 'ALPHA ❌',
                default => strtoupper($this->attendance->status)
            };
            $date = $this->attendance->clock_in?->translatedFormat('d M Y') ?? '-';
            
            $waMessage = "🔔 *NOTIFIKASI ABSENSI MANUAL*\n\n" .
                         "👤 *Nama:* {$this->user->name}\n" .
                         "📅 *Tanggal:* {$date}\n" .
                         "📊 *Status:* {$statusLabel}\n" .
                         "📝 *Catatan:* " . ($this->attendance->notes ?? '-') . "\n" .
                         "👮 *Admin:* " . $this->adminUser->name . "\n\n" .
                         "🚀 _Sistem M-Store_";
            
            $tgMessage = "🔔 *NOTIFIKASI ABSENSI MANUAL*\n\n" .
                         "👤 *Nama:* " . TelegramService::escape($this->user->name) . "\n" .
                         "📅 *Tanggal:* {$date}\n" .
                         "📊 *Status:* {$statusLabel}\n" .
                         "📝 *Catatan:* " . TelegramService::escape(($this->attendance->notes ?? '-')) . "\n" .
                         "👮 *Admin:* " . TelegramService::escape($this->adminUser->name) . "\n\n" .
                         "🚀 _Sistem M-Store_";
            
            app(WhatsAppService::class)->sendGroupNotification($waMessage, 'attendance');
            app(TelegramService::class)->sendGroupNotification($tgMessage, 'attendance');
        } catch (\Throwable $e) {
            Log::error('Manual Attendance Notification Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->user->id,
                'admin_id' => $this->adminUser->id,
                'attendance_id' => $this->attendance->id,
            ]);
        }
    }
}
