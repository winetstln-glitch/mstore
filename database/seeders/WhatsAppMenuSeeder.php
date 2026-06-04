<?php

namespace Database\Seeders;

use App\Models\WhatsAppMenu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WhatsAppMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Template 1: Salam & Halo
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'halo'],
            [
                'type' => 'text',
                'response_text' => "Halo {nama_user}! 👋\n\nSelamat datang di WhatsApp Bot kami.\nKetik *bantuan* untuk melihat menu yang tersedia.",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 10,
                'enable_fuzzy_match' => true,
            ]
        );

        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'hi'],
            [
                'type' => 'text',
                'response_text' => "Hi {nama_user}! 😊\n\nSelamat datang di WhatsApp Bot kami.\nKetik *bantuan* untuk melihat menu.",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 9,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 2: Bantuan / Menu Utama
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'bantuan'],
            [
                'type' => 'text',
                'response_text' => "📋 *Menu Bantuan WhatsApp Bot*\n\nBerikut perintah yang bisa Anda gunakan:\n\n1️⃣ *halo/hi* - Sapa bot\n2️⃣ *bantuan* - Menampilkan menu ini\n3️⃣ *absen* - Info absensi\n4️⃣ *pulang* - Info absen pulang\n5️⃣ *jadwal* - Lihat jadwal kerja\n6️⃣ *voucher* - Info voucher internet\n7️⃣ *tiket* - Buat tiket support\n8️⃣ *kontak* - Kontak kami\n\nTerima kasih! 🙏",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 10,
                'enable_fuzzy_match' => true,
            ]
        );

        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'menu'],
            [
                'type' => 'text',
                'response_text' => "📋 *Menu WhatsApp Bot*\n\n1️⃣ *halo/hi* - Sapa bot\n2️⃣ *bantuan* - Menampilkan menu ini\n3️⃣ *absen* - Info absensi\n4️⃣ *pulang* - Info absen pulang\n5️⃣ *jadwal* - Lihat jadwal kerja\n6️⃣ *voucher* - Info voucher internet\n7️⃣ *tiket* - Buat tiket support\n8️⃣ *kontak* - Kontak kami",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 9,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 3: Absensi
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'absen'],
            [
                'type' => 'text',
                'response_text' => "⏰ *Informasi Absensi Masuk*\n\nUntuk melakukan absensi masuk:\n1. Buka aplikasi MStore\n2. Klik menu Absensi\n3. Tekan tombol \"Clock In\"\n\nAtau kunjungi: " . url('/attendance/create') . "\n\nWaktu absensi masuk mulai pukul 07:00!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 8,
                'enable_fuzzy_match' => true,
            ]
        );

        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'pulang'],
            [
                'type' => 'text',
                'response_text' => "🏠 *Informasi Absensi Pulang*\n\nUntuk melakukan absensi pulang:\n1. Buka aplikasi MStore\n2. Klik menu Absensi\n3. Tekan tombol \"Clock Out\"\n\nAtau kunjungi: " . url('/attendance') . "\n\nPastikan Anda sudah selesai bekerja!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 8,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 4: Jadwal Kerja
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'jadwal'],
            [
                'type' => 'text',
                'response_text' => "📅 *Jadwal Kerja*\n\n🕐 Shift 1: 08:00 - 17:00\n🕑 Shift 2: 15:00 - 00:00\n\nUntuk melihat jadwal pribadi Anda:\n" . url('/schedules') . "\n\nHari libur sesuai kalender nasional!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 8,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 5: Voucher Internet
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'voucher'],
            [
                'type' => 'text',
                'response_text' => "💳 *Informasi Voucher Internet*\n\nUntuk membeli voucher internet:\n1. Buka halaman voucher di website\n2. Pilih paket yang diinginkan\n3. Lakukan pembayaran\n\nKunjungi: " . url('/voucher/list') . "\n\nUntuk pertanyaan lebih lanjut, silakan hubungi support!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 7,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 6: Tiket Support
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'tiket'],
            [
                'type' => 'text',
                'response_text' => "🎫 *Buat Tiket Support*\n\nUntuk membuat tiket support:\n1. Buka aplikasi MStore\n2. Klik menu Tiket\n3. Isi formulir dan kirim\n\nKunjungi: " . url('/tickets/create') . "\n\nTim kami akan segera membantu Anda!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 7,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 7: Kontak Kami
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'kontak'],
            [
                'type' => 'text',
                'response_text' => "📞 *Kontak Kami*\n\n📧 Email: support@mstore.com\n📱 Telepon: +62 812-3456-7890\n🏠 Alamat: Jl. Teknologi No. 123, Jakarta\n\nJam operasional: 08:00 - 17:00 WIB\n\nTerima kasih telah menghubungi kami!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 7,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 8: Terima Kasih
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'terima kasih'],
            [
                'type' => 'text',
                'response_text' => "Sama-sama {nama_user}! 🙏\n\nSenang bisa membantu Anda.\nJika ada pertanyaan lain, silakan hubungi kami kembali!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 6,
                'enable_fuzzy_match' => true,
            ]
        );

        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'makasih'],
            [
                'type' => 'text',
                'response_text' => "Sama-sama! 😊\n\nSenang bisa membantu Anda.",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 5,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 9: Selamat Pagi/Siang/Malam
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'selamat pagi'],
            [
                'type' => 'text',
                'response_text' => "Selamat pagi {nama_user}! 🌅\n\nSemoga hari ini penuh semangat dan produktivitas!\n\nKetik *bantuan* jika Anda membutuhkan bantuan.",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 6,
                'enable_fuzzy_match' => true,
            ]
        );

        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'selamat siang'],
            [
                'type' => 'text',
                'response_text' => "Selamat siang {nama_user}! ☀️\n\nSemoga hari Anda menyenangkan!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 6,
                'enable_fuzzy_match' => true,
            ]
        );

        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'selamat malam'],
            [
                'type' => 'text',
                'response_text' => "Selamat malam {nama_user}! 🌙\n\nSemoga Anda beristirahat dengan nyenyak!\n\nBesok tetap semangat!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 6,
                'enable_fuzzy_match' => true,
            ]
        );
    }
}
