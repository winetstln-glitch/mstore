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
                'response_text' => "Halo {nama_user}! 👋\n\nSelamat datang di WhatsApp Bot MStore.\nKetik *bantuan* untuk melihat semua menu yang tersedia.",
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
                'response_text' => "Hi {nama_user}! 😊\n\nSelamat datang di WhatsApp Bot MStore.\nKetik *bantuan* untuk melihat semua menu.",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 9,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 2: Bantuan / Menu Utama - Semua Fitur
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'bantuan'],
            [
                'type' => 'text',
                'response_text' => "📋 *Menu Lengkap WhatsApp Bot MStore*\n\nBerikut semua perintah yang bisa Anda gunakan:\n\n🌟 *Layanan Umum*:\n1️⃣ *halo/hi* - Sapa bot\n2️⃣ *bantuan* - Menampilkan menu ini\n3️⃣ *tiket* - Buat tiket support\n4️⃣ *kontak* - Kontak kami\n\n🌐 *Layanan Internet & Network*:\n5️⃣ *paket internet* - Info paket internet\n6️⃣ *voucher* - Beli voucher internet\n7️⃣ *cctv* - Layanan pasang CCTV\n8️⃣ *instalasi* - Pemasangan internet\n\n🧼 *Layanan Wash*:\n9️⃣ *wash* - Info layanan cuci kendaraan\n\n🏪 *Toko ATK*:\n🔟 *atk* - Info produk ATK\n\n💒 *Layanan Wedding & Event*:\n1️⃣1️⃣ *wedding* - Info layanan wedding & event\n\n👥 *Karyawan & Internal*:\n1️⃣2️⃣ *absen* - Info absensi masuk\n1️⃣3️⃣ *pulang* - Info absensi pulang\n1️⃣4️⃣ *jadwal* - Lihat jadwal kerja\n\nTerima kasih! 🙏",
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
                'response_text' => "📋 *Menu WhatsApp Bot MStore*\n\n1️⃣ *halo/hi* - Sapa bot\n2️⃣ *bantuan* - Menampilkan menu lengkap\n3️⃣ *tiket* - Buat tiket support\n4️⃣ *paket internet* - Info paket internet\n5️⃣ *voucher* - Beli voucher internet\n6️⃣ *cctv* - Layanan pasang CCTV\n7️⃣ *wash* - Info layanan cuci kendaraan\n8️⃣ *atk* - Info produk ATK\n9️⃣ *wedding* - Info layanan wedding & event\n🔟 *absen* - Info absensi\n1️⃣1️⃣ *kontak* - Kontak kami",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 9,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 3: Paket Internet
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'paket internet'],
            [
                'type' => 'text',
                'response_text' => "🌐 *Paket Internet MStore*\n\nKami menyediakan berbagai paket internet untuk kebutuhan Anda:\n- Paket Rumah 10 Mbps\n- Paket Rumah 20 Mbps\n- Paket Bisnis 50 Mbps\n- Paket Bisnis 100 Mbps\n\nUntuk melihat dan membeli paket:\n1. Buka aplikasi MStore\n2. Klik menu Paket Internet\n\nAtau kunjungi: " . url('/packages') . "\n\nUntuk pertanyaan lebih lanjut, buat tiket support atau hubungi kami!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 8,
                'enable_fuzzy_match' => true,
            ]
        );

        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'internet'],
            [
                'type' => 'text',
                'response_text' => "🌐 *Layanan Internet MStore*\n\nUntuk melihat paket internet, ketik *paket internet*.\nUntuk beli voucher, ketik *voucher*.\nUntuk pemasangan baru, ketik *instalasi*.\n\nUntuk keluhan, buat tiket support dengan ketik *tiket*!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 7,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 4: Instalasi & CCTV
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'instalasi'],
            [
                'type' => 'text',
                'response_text' => "🔧 *Layanan Pemasangan & Instalasi*\n\nKami menyediakan layanan pemasangan:\n1. Instalasi internet baru\n2. Pemasangan CCTV\n3. Pemasangan jaringan kantor\n\nUntuk meminta layanan instalasi:\n1. Buka aplikasi MStore\n2. Buat tiket support dengan memilih kategori \"Instalasi\"\n\nKunjungi: " . url('/tickets/create') . "\n\nTim teknisi kami akan segera menghubungi Anda!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 8,
                'enable_fuzzy_match' => true,
            ]
        );

        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'cctv'],
            [
                'type' => 'text',
                'response_text' => "📹 *Layanan Pasang CCTV*\n\nLindungi properti Anda dengan layanan pasang CCTV profesional dari kami!\n- Paket CCTV 2 Kamera\n- Paket CCTV 4 Kamera\n- Paket CCTV 8 Kamera\n- Paket Custom\n\nUntuk meminta layanan CCTV:\n1. Buka aplikasi MStore\n2. Buat tiket support dengan memilih kategori \"CCTV\"\n\nKunjungi: " . url('/tickets/create') . "\n\nTim kami akan memberikan konsultasi dan penawaran terbaik!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 8,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 5: Layanan Wash
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'wash'],
            [
                'type' => 'text',
                'response_text' => "🧼 *Layanan Cuci Kendaraan MStore Wash*\n\nKami menyediakan layanan cuci kendaraan berkualitas:\n- Cuci Mobil Standar\n- Cuci Mobil Premium\n- Cuci Motor\n- Detailing Kendaraan\n- Salon Kendaraan\n\nUntuk melihat semua layanan dan harga:\n1. Buka aplikasi MStore\n2. Klik menu Wash\n\nAtau kunjungi: " . url('/wash') . "\n\nNikmati cuci kendaraan bersih dan rapi dengan harga terjangkau!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 8,
                'enable_fuzzy_match' => true,
            ]
        );

        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'cuci'],
            [
                'type' => 'text',
                'response_text' => "🧼 *Layanan Cuci Kendaraan*\n\nUntuk info layanan cuci kendaraan, ketik *wash* ya!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 7,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 6: Toko ATK
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'atk'],
            [
                'type' => 'text',
                'response_text' => "🏪 *Toko ATK MStore*\n\nKami menyediakan berbagai kebutuhan ATK lengkap:\n- Alat tulis (pulpen, pensil, buku, dll)\n- Kertas dan perlengkapan kantor\n- Perlengkapan sekolah\n- Dan masih banyak lagi!\n\nUntuk melihat katalog produk:\n1. Buka aplikasi MStore\n2. Klik menu Toko ATK\n\nAtau kunjungi: " . url('/atk') . "\n\nBelanja ATK mudah dan cepat di MStore!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 8,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 7: Layanan Wedding & Event
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'wedding'],
            [
                'type' => 'text',
                'response_text' => "💒 *Layanan Wedding & Event MStore*\n\nWujudkan acara impian Anda dengan layanan event organizer profesional dari kami!\n- Pernikahan (Wedding)\n- Ulang tahun\n- Acara perusahaan\n- Seminar dan workshop\n- Dan acara lainnya\n\nLayanan kami:\n- Dekorasi acara\n- Dokumentasi foto & video\n- Catering\n- Sound system\n- MC\n\nUntuk konsultasi dan pemesanan:\n1. Buka aplikasi MStore\n2. Buat tiket support dengan kategori \"Wedding & Event\"\n\nKunjungi: " . url('/tickets/create') . "\n\nTim event kami siap membantu Anda! 🎉",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 8,
                'enable_fuzzy_match' => true,
            ]
        );

        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'event'],
            [
                'type' => 'text',
                'response_text' => "🎉 *Layanan Wedding & Event*\n\nUntuk info layanan wedding dan event, ketik *wedding* ya!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 7,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 8: Absensi
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

        // Template 9: Jadwal Kerja
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

        // Template 10: Voucher Internet
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

        // Template 11: Tiket Support
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

        // Template 12: Kontak Kami
        WhatsAppMenu::updateOrCreate(
            ['keyword' => 'kontak'],
            [
                'type' => 'text',
                'response_text' => "📞 *Kontak Kami*\n\n📧 Email: support@mstore.com\n📱 Telepon: +62 812-3456-7890\n🏠 Alamat: Jl. Simpang Binuanageun Gunggur, Sukatan\n\nJam operasional: 08:00 - 17:00 WIB\n\nTerima kasih telah menghubungi kami!",
                'is_active' => true,
                'hits_count' => 0,
                'priority' => 7,
                'enable_fuzzy_match' => true,
            ]
        );

        // Template 13: Terima Kasih
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

        // Template 14: Selamat Pagi/Siang/Malam
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
