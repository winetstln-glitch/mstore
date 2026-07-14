<?php

namespace App\Services\WhatsApp;

use App\Jobs\WhatsApp\SendWhatsAppMessageJob;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Attendance\AttendanceService;
use App\Services\Schedule\ScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WhatsAppIntegrationRouter
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly ScheduleService $scheduleService,
    ) {}

    public function routeIncomingMessage(string $from, string $message, ?User $user = null): ?string
    {
        $message = strtolower(trim($message));

        if (!$user) {
            return "Maaf, nomor WhatsApp Anda tidak terdaftar di sistem kami.\nSilakan hubungi admin untuk mendaftarkan nomor Anda.";
        }

        if ($this->matchesKeywords($message, ['jadwal hari ini', 'jadwal hari ini', 'schedule today', 'shift hari ini'])) {
            return $this->handleScheduleToday($user);
        }

        if ($this->matchesKeywords($message, ['jadwal besok', 'schedule tomorrow', 'shift besok'])) {
            return $this->handleScheduleTomorrow($user);
        }

        if ($this->matchesKeywords($message, ['status absen', 'status absensi', 'absen hari ini', 'cek absen'])) {
            return $this->handleAttendanceStatus($user);
        }

        if ($this->matchesKeywords($message, ['cek tiket', 'tiket aktif', 'tugas saya', 'my tickets'])) {
            return $this->handleMyTickets($user);
        }

        if ($this->matchesKeywords($message, ['tiket baru', 'new ticket'])) {
            return $this->handleNewTicketInfo();
        }

        if ($this->matchesKeywords($message, ['halo', 'hi', 'hello', 'hey', 'assalamualaikum', 'assalamu\'alaikum'])) {
            return $this->handleGreeting($user);
        }

        if ($this->matchesKeywords($message, ['bantuan', 'help', 'menu', 'perintah'])) {
            return $this->handleHelpMenu();
        }

        return null;
    }

    public function sendAttendanceNotification(User $user, string $action, array $data = []): void
    {
        $groupEnabled = Setting::getValue('whatsapp_attendance_notification_enabled', '1') == '1';
        $groupId = Setting::getValue('whatsapp_attendance_group_id', '');

        if ($groupEnabled && $groupId) {
            $message = $this->formatAttendanceNotification($user, $action, $data);
            SendWhatsAppMessageJob::dispatch($groupId, $message);
        }

        $message = $this->formatAttendanceNotification($user, $action, $data, true);
        SendWhatsAppMessageJob::dispatch($user->phone, $message);
    }

    public function sendNewTicketNotification(Ticket $ticket): void
    {
        $groupEnabled = Setting::getValue('whatsapp_ticket_notification_enabled', '1') == '1';
        $groupId = Setting::getValue('whatsapp_ticket_group_id', '');

        if (!$groupEnabled || !$groupId) {
            return;
        }

        $message = $this->formatNewTicketNotification($ticket);
        SendWhatsAppMessageJob::dispatch($groupId, $message);
    }

    public function sendModemAlert(string $deviceName, string $status, array $data = []): void
    {
        $isDown = strtolower($status) === 'down';
        $settingKey = $isDown ? 'whatsapp_modem_down_notification_enabled' : 'whatsapp_modem_up_notification_enabled';
        $groupKey = $isDown ? 'whatsapp_modem_down_group_id' : 'whatsapp_modem_up_group_id';

        $enabled = Setting::getValue($settingKey, '1') == '1';
        $groupId = Setting::getValue($groupKey, '');

        if (!$enabled || !$groupId) {
            return;
        }

        $message = $this->formatModemAlert($deviceName, $status, $data);
        SendWhatsAppMessageJob::dispatch($groupId, $message);
    }

    private function matchesKeywords(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function handleGreeting(User $user): string
    {
        $hour = Carbon::now()->hour;
        $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 18 ? 'Selamat Siang' : 'Selamat Malam');

        return "{$greeting} {$user->name}! 👋\n\n" .
               "Selamat datang di WhatsApp Bot MStore.\n" .
               "Ketik *bantuan* untuk melihat semua perintah yang tersedia.";
    }

    private function handleHelpMenu(): string
    {
        return "📋 *Menu Bantuan WhatsApp Bot Pro*\n\n" .
               "🔹 Jadwal & Shift\n" .
               "   • *jadwal hari ini* - Lihat jadwal shift hari ini\n" .
               "   • *jadwal besok* - Lihat jadwal shift besok\n\n" .
               "🔹 Absensi\n" .
               "   • *status absen* - Cek status absensi hari ini\n\n" .
               "🔹 Tiket & Tugas\n" .
               "   • *cek tiket* - Lihat daftar tiket aktif\n" .
               "   • *tiket baru* - Info tiket baru\n\n" .
               "🔹 Umum\n" .
               "   • *halo* - Sapa bot\n" .
               "   • *bantuan* - Menampilkan menu ini\n\n" .
               "Terima kasih! 🙏";
    }

    private function handleScheduleToday(User $user): string
    {
        $today = Carbon::today();
        $data = $this->scheduleService->buildWeeksData([$user], $today->year, $today->weekOfYear);
        $weekData = $data['weeks_data'][$today->weekOfYear] ?? [];
        $dayKey = $today->format('Y-m-d');

        $scheduleEntry = collect($weekData['users'] ?? [])
            ->where('user_id', $user->id)
            ->first();

        $status = $scheduleEntry['days'][$dayKey]['status'] ?? '-';

        $message = "📅 *Jadwal Shift Hari Ini*\n\n";
        $message .= "Nama: {$user->name}\n";
        $message .= "Tanggal: {$today->translatedFormat('l, d F Y')}\n";
        $message .= "Status: {$this->scheduleService->applyScheduleDisplayNames(['status' => $status])['status']}\n";

        return $message;
    }

    private function handleScheduleTomorrow(User $user): string
    {
        $tomorrow = Carbon::tomorrow();
        $data = $this->scheduleService->buildWeeksData([$user], $tomorrow->year, $tomorrow->weekOfYear);
        $weekData = $data['weeks_data'][$tomorrow->weekOfYear] ?? [];
        $dayKey = $tomorrow->format('Y-m-d');

        $scheduleEntry = collect($weekData['users'] ?? [])
            ->where('user_id', $user->id)
            ->first();

        $status = $scheduleEntry['days'][$dayKey]['status'] ?? '-';

        $message = "📅 *Jadwal Shift Besok*\n\n";
        $message .= "Nama: {$user->name}\n";
        $message .= "Tanggal: {$tomorrow->translatedFormat('l, d F Y')}\n";
        $message .= "Status: {$this->scheduleService->applyScheduleDisplayNames(['status' => $status])['status']}\n";

        return $message;
    }

    private function handleAttendanceStatus(User $user): string
    {
        $today = Carbon::today();
        $attendance = $this->attendanceService->getTodayAttendance($user);

        $message = "⏱️ *Status Absensi Hari Ini*\n\n";
        $message .= "Nama: {$user->name}\n";
        $message .= "Tanggal: {$today->translatedFormat('l, d F Y')}\n\n";

        if ($attendance) {
            $message .= "Status: " . strtoupper($attendance->status) . "\n";
            if ($attendance->clock_in) {
                $message .= "Jam Masuk: {$attendance->clock_in->format('H:i')}\n";
            }
            if ($attendance->clock_out) {
                $message .= "Jam Pulang: {$attendance->clock_out->format('H:i')}\n";
            }
            if ($attendance->notes) {
                $message .= "Catatan: {$attendance->notes}\n";
            }
        } else {
            $message .= "Status: BELUM ABSEN\n";
            $message .= "Silakan lakukan absensi masuk di aplikasi!\n";
        }

        return $message;
    }

    private function handleMyTickets(User $user): string
    {
        $tickets = Ticket::where('assigned_to', $user->id)
            ->whereIn('status', ['open', 'in_progress', 'pending'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $message = "🎫 *Daftar Tiket Aktif Anda*\n\n";

        if ($tickets->isEmpty()) {
            $message .= "Tidak ada tiket aktif yang ditugaskan kepada Anda saat ini.\n";
            return $message;
        }

        foreach ($tickets as $index => $ticket) {
            $no = $index + 1;
            $message .= "{$no}. #{$ticket->id} - {$ticket->title}\n";
            $message .= "   Status: " . strtoupper($ticket->status) . "\n";
            $message .= "   Dibuat: {$ticket->created_at->translatedFormat('d M Y H:i')}\n\n";
        }

        $message .= "Total {$tickets->count()} tiket aktif.";

        return $message;
    }

    private function handleNewTicketInfo(): string
    {
        return "🎫 *Info Tiket Baru*\n\n" .
               "Untuk melihat tiket baru, silakan:\n" .
               "1. Buka aplikasi MStore\n" .
               "2. Navigasi ke menu Tiket\n" .
               "3. Pilih tiket yang ingin Anda tangani\n\n" .
               "Atau ketik *cek tiket* untuk melihat daftar tiket yang ditugaskan kepada Anda.";
    }

    private function formatAttendanceNotification(User $user, string $action, array $data, bool $personal = false): string
    {
        $now = Carbon::now()->translatedFormat('d F Y H:i');

        if ($personal) {
            if ($action === 'clock_in') {
                $icon = '✅';
                $text = "Anda berhasil melakukan ABSENSI MASUK";
            } elseif ($action === 'clock_out') {
                $icon = '✅';
                $text = "Anda berhasil melakukan ABSENSI PULANG";
            } elseif ($action === 'alpha') {
                $icon = '⚠️';
                $text = "Anda tercatat sebagai ALPHA hari ini";
            } else {
                $icon = 'ℹ️';
                $text = "Notifikasi absensi";
            }

            $message = "{$icon} *Notifikasi Absensi Pribadi*\n\n";
            $message .= "{$text}!\n";
            $message .= "Waktu: {$now}";
        } else {
            if ($action === 'clock_in') {
                $icon = '✅';
                $text = "ABSENSI MASUK";
            } elseif ($action === 'clock_out') {
                $icon = '✅';
                $text = "ABSENSI PULANG";
            } elseif ($action === 'alpha') {
                $icon = '⚠️';
                $text = "MARKED AS ALPHA";
            } else {
                $icon = 'ℹ️';
                $text = "NOTIFIKASI ABSENSI";
            }

            $message = "{$icon} *{$text}*\n\n";
            $message .= "Nama: {$user->name}\n";
            $message .= "Waktu: {$now}";
        }

        return $message;
    }

    private function formatNewTicketNotification(Ticket $ticket): string
    {
        $message = "🎫 *TIKET BARU TERSEDIA!*\n\n";
        $message .= "ID Tiket: #{$ticket->id}\n";
        $message .= "Judul: {$ticket->title}\n";
        $message .= "Status: " . strtoupper($ticket->status) . "\n";
        $message .= "Dibuat: {$ticket->created_at->translatedFormat('d F Y H:i')}\n";

        if ($ticket->description) {
            $message .= "\nDeskripsi:\n" . substr($ticket->description, 0, 200);
            if (strlen($ticket->description) > 200) {
                $message .= "...";
            }
        }

        $message .= "\n\nSilakan buka aplikasi untuk menindaklanjuti tiket ini!";

        return $message;
    }

    private function formatModemAlert(string $deviceName, string $status, array $data): string
    {
        $isDown = strtolower($status) === 'down';
        $icon = $isDown ? '🔴' : '🟢';
        $statusText = $isDown ? 'DOWN' : 'UP';

        $message = "{$icon} *ALERT MODEM {$statusText}*\n\n";
        $message .= "Nama Perangkat: {$deviceName}\n";
        $message .= "Status: {$statusText}\n";
        $message .= "Waktu: " . Carbon::now()->translatedFormat('d F Y H:i:s') . "\n";

        if (isset($data['location'])) {
            $message .= "Lokasi: {$data['location']}\n";
        }

        if (isset($data['customer'])) {
            $message .= "Pelanggan: {$data['customer']}\n";
        }

        return $message;
    }
}
