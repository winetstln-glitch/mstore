<?php

namespace App\Services\WhatsApp;

use App\Models\Setting;
use Illuminate\Support\Arr;

class WhatsAppTemplateService
{
    /**
     * List of all available WhatsApp notification templates
     */
    public function getTemplateDefinitions(): array
    {
        return [
            'ticket_created_whatsapp_template' => [
                'default' => "🎫 *TIKET BARU: {ticket_number}*\n\n" .
                    "📌 *Tipe:* {ticket_type}\n" .
                    "👤 *Pelanggan:* {customer_name}\n" .
                    "👷 *Teknisi:* {technician_names}\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "⚡ *Prioritas:* {ticket_priority}\n" .
                    "📍 *Alamat:* {ticket_address}\n\n" .
                    "🔗 *Detail:* {ticket_url}\n\n" .
                    "🚀 _Sistem M-Store_",
                'label' => 'Template Notifikasi WhatsApp: Tiket Baru (Grup)',
                'placeholders' => ['ticket_number', 'ticket_type', 'customer_name', 'ticket_subject', 'ticket_priority', 'ticket_address', 'ticket_url', 'technician_name', 'technician_names'],
            ],
            'ticket_assigned_whatsapp_template' => [
                'default' => "*TUGAS BARU (TICKET ASSIGNED)*\n\n" .
                    "Halo {technician_name},\n" .
                    "Anda telah ditugaskan untuk tiket berikut:\n\n" .
                    "🎫 *No Tiket:* {ticket_number}\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "👤 *Customer:* {customer_name}\n" .
                    "📍 *Lokasi:* {ticket_location}\n" .
                    "⚡ *Prioritas:* {ticket_priority}\n" .
                    "📄 *Deskripsi:* {ticket_description}\n\n" .
                    "Segera proses tiket ini melalui link berikut:\n{ticket_url}",
                'label' => 'Template Notifikasi WhatsApp: Tiket Diberikan (Teknisi)',
                'placeholders' => ['technician_name', 'ticket_number', 'ticket_subject', 'customer_name', 'ticket_location', 'ticket_priority', 'ticket_description', 'ticket_url'],
            ],
            'ticket_status_updated_whatsapp_template' => [
                'default' => "🔄 *STATUS TIKET DIPERBARUI*\n\n" .
                    "🎫 *No Tiket:* {ticket_number}\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "🔄 *Status Baru:* {new_status}\n" .
                    "👤 *Diperbarui Oleh:* {updated_by}\n\n" .
                    "🔗 *Detail:* {ticket_url}",
                'label' => 'Template Notifikasi WhatsApp: Status Tiket Diperbarui',
                'placeholders' => ['ticket_number', 'ticket_subject', 'new_status', 'updated_by', 'ticket_url'],
            ],
            'ticket_solved_whatsapp_template' => [
                'default' => "✅ *TIKET SELESAI: {ticket_number}*\n\n" .
                    "👤 *Pelanggan:* {customer_name}\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "🛠️ *Oleh:* {updated_by}\n" .
                    "🗒️ *Hasil:* {ticket_note}\n\n" .
                    "🚀 _Sistem M-Store_",
                'label' => 'Template Notifikasi WhatsApp: Tiket Selesai (Grup)',
                'placeholders' => ['ticket_number', 'customer_name', 'ticket_subject', 'updated_by', 'ticket_note'],
            ],
            'ticket_assigned_group_whatsapp_template' => [
                'default' => "🎫 *PENUGASAN TIKET: {ticket_number}*\n\n" .
                    "📝 *Subjek:* {ticket_subject}\n" .
                    "👷 *Teknisi:* {technician_names}\n" .
                    "👤 *Oleh:* {updated_by}\n" .
                    "🔗 *Detail:* {ticket_url}\n\n" .
                    "🚀 _Sistem M-Store_",
                'label' => 'Template Notifikasi WhatsApp: Teknisi Ditugaskan (Grup)',
                'placeholders' => ['ticket_number', 'ticket_subject', 'technician_name', 'technician_names', 'updated_by', 'ticket_url'],
            ],
            'whatsapp_atk_receipt_template' => [
                'default' => "🧾 *STRUK PEMBELIAN*\n\n🏪 {{nama_toko}}\n📍 {{alamat_toko}}\n☎ {{no_toko}}\n\n━━━━━━━━━━━━━━━━━━\n\nNo Invoice : {{invoice}}\nTanggal    : {{tanggal}}\nCustomer   : {{nama_customer}}\n\n━━━━━━━━━━━━━━━━━━\n\n📦 Detail Barang:\n\n{{#each items}}\n{{nama_produk}}\n{{qty}} x Rp{{harga}} = Rp{{total}}\n{{/each}}\n\n━━━━━━━━━━━━━━━━━━\nSubtotal : Rp{{subtotal}}\nDiskon   : Rp{{diskon}}\nPajak    : Rp{{pajak}}\n━━━━━━━━━━━━━━━━━━\n💰 *Total Bayar : Rp{{grand_total}}*\n\nMetode Bayar : {{metode_bayar}}\nStatus       : {{status}}\n\n━━━━━━━━━━━━━━━━━━\nTerima kasih telah berbelanja 🙏",
                'label' => 'ATK Receipt Template',
                'placeholders' => ['nama_toko', 'alamat_toko', 'no_toko', 'invoice', 'tanggal', 'nama_customer', 'items', 'subtotal', 'diskon', 'pajak', 'grand_total', 'metode_bayar', 'status'],
            ],
            'whatsapp_wash_receipt_template' => [
                'default' => "🚗 *STRUK LAYANAN CUCI KENDARAAN*\n\n🏪 {{nama_usaha}}\n📍 {{alamat}}\n☎ {{no_hp}}\n\n━━━━━━━━━━━━━━━━━━\n\nNo Transaksi : {{invoice}}\nTanggal      : {{tanggal}}\nCustomer     : {{nama_customer}}\nKendaraan    : {{jenis_kendaraan}} - {{plat_nomor}}\n\n━━━━━━━━━━━━━━━━━━\n\n🧼 Layanan:\n{{#each items}}\n• {{nama_layanan}} - Rp{{harga}}\n{{/each}}\n\n━━━━━━━━━━━━━━━━━━\nSubtotal : Rp{{subtotal}}\nDiskon   : Rp{{diskon}}\n━━━━━━━━━━━━━━━━━━\n💰 *Total Bayar : Rp{{total}}*\n\nMetode Bayar : {{metode_bayar}}\nStatus       : {{status}}\n\n━━━━━━━━━━━━━━━━━━\nTerima kasih 🙏",
                'label' => 'Wash Receipt Template',
                'placeholders' => ['nama_usaha', 'alamat', 'no_hp', 'invoice', 'tanggal', 'nama_customer', 'jenis_kendaraan', 'plat_nomor', 'items', 'subtotal', 'diskon', 'total', 'metode_bayar', 'status'],
            ],
            'whatsapp_atk_invoice_pdf_template' => [
                'default' => "🧾 *INVOICE PEMBELIAN*\n\nHalo {{nama_customer}},\n\nInvoice {{invoice}} sebesar Rp{{grand_total}}.\n\nDownload nota PDF di sini:\n{{link_pdf}}\n\nTerima kasih 🙏",
                'label' => 'ATK Invoice PDF Template',
                'placeholders' => ['nama_customer', 'invoice', 'grand_total', 'link_pdf'],
            ],
            'whatsapp_wash_ready_template' => [
                'default' => "🚗 *KENDARAAN SELESAI DICUCI*\n\nHalo {{nama_customer}},\n\nKendaraan Anda:\n{{jenis_kendaraan}} - {{plat_nomor}}\n\nSudah selesai dan siap diambil 🙏\n\nTotal pembayaran: Rp{{total}}\n\nTerima kasih sudah menggunakan layanan kami.",
                'label' => 'Wash Ready Template',
                'placeholders' => ['nama_customer', 'jenis_kendaraan', 'plat_nomor', 'total'],
            ],
            'whatsapp_isp_bill_template' => [
                'default' => "🌐 *TAGIHAN INTERNET BULANAN*\n\nHalo {{nama_customer}},\n\nBerikut detail tagihan Anda:\n\nID Pelanggan : {{customer_id}}\nPeriode      : {{periode}}\n\n━━━━━━━━━━━━━━━━━━\nPaket        : {{nama_paket}}\nBiaya Paket  : Rp{{harga_paket}}\nAdmin        : Rp{{biaya_admin}}\n━━━━━━━━━━━━━━━━━━\n💰 *Total Tagihan : Rp{{total}}*\n\nJatuh Tempo  : {{jatuh_tempo}}\nStatus       : {{status}}\n\nSilakan lakukan pembayaran sebelum jatuh tempo 🙏",
                'label' => 'ISP Monthly Bill Template',
                'placeholders' => ['nama_customer', 'customer_id', 'periode', 'nama_paket', 'harga_paket', 'biaya_admin', 'total', 'jatuh_tempo', 'status'],
            ],
            'whatsapp_isp_reminder_template' => [
                'default' => "⏰ *PENGINGAT TAGIHAN INTERNET*\n\nHalo {{nama_customer}},\n\nTagihan internet Anda sebesar\nRp{{total}}\n\nAkan jatuh tempo pada:\n{{jatuh_tempo}}\n\nMohon segera lakukan pembayaran agar layanan tetap aktif 🙏",
                'label' => 'ISP Reminder Template',
                'placeholders' => ['nama_customer', 'total', 'jatuh_tempo'],
            ],
            'whatsapp_isp_payment_success_template' => [
                'default' => "✅ *PEMBAYARAN DITERIMA*\n\nHalo {{nama_customer}},\n\nPembayaran internet periode {{periode}}\nsebesar Rp{{total}}\nteleh kami terima.\n\nStatus layanan: AKTIF\n\nTerima kasih atas kepercayaannya 🙏",
                'label' => 'ISP Payment Success Template',
                'placeholders' => ['nama_customer', 'periode', 'total'],
            ],
            'whatsapp_isp_suspend_template' => [
                'default' => "⚠ *PEMBERITAHUAN LAYANAN*\n\nHalo {{nama_customer}},\n\nKarena belum ada pembayaran hingga melewati jatuh tempo,\nlayanan internet Anda saat ini dinonaktifkan sementara.\n\nTotal tunggakan: Rp{{total}}\n\nSilakan lakukan pembayaran agar layanan kembali aktif.",
                'label' => 'ISP Suspend Template',
                'placeholders' => ['nama_customer', 'total'],
            ],
            'whatsapp_unknown_keyword_reply' => [
                'default' => "Maaf, saya tidak memahami pesan Anda. Silakan ketik \"bantuan\" untuk melihat daftar menu yang tersedia.",
                'label' => 'Balasan untuk Keyword Tidak Dikenali',
                'placeholders' => [],
            ],
        ];
    }

    /**
     * Get all WhatsApp settings from database or defaults
     */
    public function getAllSettings(): array
    {
        $templateDefs = $this->getTemplateDefinitions();
        $settings = [];
        
        foreach ($templateDefs as $key => $def) {
            $setting = Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $def['default'],
                    'group' => 'whatsapp',
                    'type' => 'textarea',
                    'label' => $def['label'],
                ]
            );
            if (blank($setting->value)) {
                $setting->update(['value' => $def['default']]);
            }
            $settings[$key] = $setting;
        }

        // Add core WhatsApp settings
        $coreSettings = [
            'whatsapp_api_url' => ['default' => env('WHATSAPP_API_URL'), 'type' => 'text', 'label' => 'WhatsApp API URL'],
            'whatsapp_api_key' => ['default' => env('WHATSAPP_API_KEY'), 'type' => 'password', 'label' => 'WhatsApp API Key'],
            'whatsapp_secret_key' => ['default' => env('WHATSAPP_SECRET_KEY', ''), 'type' => 'password', 'label' => 'WABLAS Secret Key'],
            'whatsapp_ticket_notification_enabled' => ['default' => '1', 'type' => 'boolean', 'label' => 'WhatsApp Ticket Notification Enabled'],
            'whatsapp_attendance_notification_enabled' => ['default' => '1', 'type' => 'boolean', 'label' => 'WhatsApp Attendance Notification Enabled'],
            'whatsapp_modem_up_notification_enabled' => ['default' => '1', 'type' => 'boolean', 'label' => 'WhatsApp Modem UP Notification Enabled'],
            'whatsapp_modem_down_notification_enabled' => ['default' => '1', 'type' => 'boolean', 'label' => 'WhatsApp Modem DOWN Notification Enabled'],
            'whatsapp_modem_recap_notification_enabled' => ['default' => '1', 'type' => 'boolean', 'label' => 'WhatsApp Modem RECAP Notification Enabled'],
            'whatsapp_autoreply_enabled' => ['default' => '1', 'type' => 'boolean', 'label' => 'WhatsApp Auto Reply Enabled'],
            'whatsapp_ticket_group_id' => ['default' => Setting::getValue('whatsapp_group_notification_id', ''), 'type' => 'text', 'label' => 'WhatsApp Ticket Group ID'],
            'whatsapp_attendance_group_id' => ['default' => Setting::getValue('whatsapp_group_notification_id', ''), 'type' => 'text', 'label' => 'WhatsApp Attendance Group ID'],
            'whatsapp_modem_up_group_id' => ['default' => Setting::getValue('whatsapp_group_notification_id', ''), 'type' => 'text', 'label' => 'WhatsApp Modem UP Group ID'],
            'whatsapp_modem_down_group_id' => ['default' => Setting::getValue('whatsapp_group_notification_id', ''), 'type' => 'text', 'label' => 'WhatsApp Modem DOWN Group ID'],
            'whatsapp_modem_recap_group_id' => ['default' => Setting::getValue('whatsapp_group_notification_id', ''), 'type' => 'text', 'label' => 'WhatsApp Modem RECAP Group ID'],
        ];

        foreach ($coreSettings as $key => $def) {
            $setting = Setting::firstOrCreate(['key' => $key], [
                'value' => $def['default'],
                'group' => 'whatsapp',
                'type' => $def['type'],
                'label' => $def['label'],
            ]);
            $settings[$key] = $setting;
        }

        return $settings;
    }

    /**
     * Mask API key for safe display
     */
    public function maskApiKey(?string $key): ?string
    {
        if (blank($key)) {
            return null;
        }
        return str_repeat('*', max(strlen($key) - 4, 0)) . substr($key, -4);
    }
}
