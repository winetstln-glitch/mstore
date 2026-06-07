<?php

namespace App\Services\WhatsApp;

use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppMenu;

class WhatsAppAutoReplyService
{
    public function __construct() {}

    public function getReply(string $message, ?User $user = null): ?string
    {
        $message = strtolower(trim($message));

        if ($this->matchKeyword($message, ['halo', 'hi', 'hello', 'hey'])) {
            return $this->getGreetingReply($user);
        }

        if ($this->matchKeyword($message, ['bantuan', 'help', 'menu'])) {
            return $this->getHelpReply();
        }

        return null;
    }

    private function matchKeyword(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function getGreetingReply(?User $user): string
    {
        $name = $user?->name ?? 'Teman';
        return "Halo {$name}! 👋\n\nSelamat datang di WhatsApp Bot kami.\n\nKetik *bantuan* untuk melihat menu yang tersedia.";
    }

    private function getAttendanceReply(?User $user): string
    {
        if (!$user) {
            return "Maaf, Anda tidak terdaftar sebagai karyawan. Silakan hubungi admin untuk mendaftarkan nomor WhatsApp Anda.";
        }

        return "Untuk melakukan absensi masuk, silakan kunjungi:\n" . url('/attendance/create') . "\n\nAtau gunakan fitur absensi di aplikasi!";
    }

    private function getClockOutReply(?User $user): string
    {
        if (!$user) {
            return "Maaf, Anda tidak terdaftar sebagai karyawan.";
        }

        return "Untuk melakukan absensi pulang, silakan kunjungi:\n" . url('/attendance') . "\n\nAtau gunakan fitur absensi di aplikasi!";
    }

    private function getHelpReply(): string
    {
        $menuText = "📋 *Menu Bantuan WhatsApp Bot*\n\n";
        
        // Add default internal menus first
        $menuText .= "🔹 *Menu Utama*\n";
        $menuText .= "• *halo* - Sapa bot\n";
        $menuText .= "• *bantuan* - Menampilkan menu ini\n\n";
        
        // Add menus from WhatsAppMenu (bot builder)
        $menus = WhatsAppMenu::active()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($menus->isNotEmpty()) {
            $menuText .= "🔹 *Layanan Kami*\n";
            foreach ($menus as $menu) {
                $menuText .= "• *{$menu->keyword}*\n";
            }
            $menuText .= "\n";
        }
        
        $menuText .= "Terima kasih! 🙏";
        
        return $menuText;
    }

    private function getScheduleReply(): string
    {
        $shift1Start = Setting::getValue('schedule_teknisi_shift_1_start', '08:00');
        $shift1End = Setting::getValue('schedule_teknisi_shift_1_end', '17:00');
        $shift2Start = Setting::getValue('schedule_teknisi_shift_2_start', '15:00');
        $shift2End = Setting::getValue('schedule_teknisi_shift_2_end', '00:00');

        return "📅 *Jadwal Kerja*\n\n" .
               "🕐 Shift 1: {$shift1Start} - {$shift1End}\n" .
               "🕑 Shift 2: {$shift2Start} - {$shift2End}\n\n" .
               "Untuk melihat jadwal pribadi Anda, silakan kunjungi:\n" . url('/schedules');
    }

    public function getUserByPhone(string $phone): ?User
    {
        $phone = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return User::where('phone', $phone)
            ->orWhere('phone', '0' . substr($phone, 2))
            ->first();
    }
}
