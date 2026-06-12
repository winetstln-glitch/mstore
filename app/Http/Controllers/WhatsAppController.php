<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WhatsAppController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:chat.view', only: ['index', 'logs']),
            new Middleware('permission:chat.manage', only: ['update', 'test', 'checkStatus']),
        ];
    }

    /**
     * Display the WhatsApp settings page.
     */
    public function index()
    {
        $defaultTemplate = \App\Notifications\TicketAssignedNotification::defaultTemplate();

        $template = Setting::firstOrCreate(
            ['key' => 'whatsapp_ticket_template'],
            [
                'value' => $defaultTemplate,
                'group' => 'whatsapp',
                'type' => 'textarea',
                'label' => 'Ticket Notification Template',
            ]
        );
        if (blank($template->value)) {
            $template->value = $defaultTemplate;
            $template->save();
        }

        $defaultAtkReceipt = "🧾 *STRUK PEMBELIAN*\n\n🏪 {{nama_toko}}\n📍 {{alamat_toko}}\n☎ {{no_toko}}\n\n━━━━━━━━━━━━━━━━━━\n\nNo Invoice : {{invoice}}\nTanggal    : {{tanggal}}\nCustomer   : {{nama_customer}}\n\n━━━━━━━━━━━━━━━━━━\n\n📦 Detail Barang:\n\n{{#each items}}\n{{nama_produk}}\n{{qty}} x Rp{{harga}} = Rp{{total}}\n{{/each}}\n\n━━━━━━━━━━━━━━━━━━\nSubtotal : Rp{{subtotal}}\nDiskon   : Rp{{diskon}}\nPajak    : Rp{{pajak}}\n━━━━━━━━━━━━━━━━━━\n💰 *Total Bayar : Rp{{grand_total}}*\n\nMetode Bayar : {{metode_bayar}}\nStatus       : {{status}}\n\n━━━━━━━━━━━━━━━━━━\nTerima kasih telah berbelanja 🙏";
        $defaultWashReceipt = "🚗 *STRUK LAYANAN CUCI KENDARAAN*\n\n🏪 {{nama_usaha}}\n📍 {{alamat}}\n☎ {{no_hp}}\n\n━━━━━━━━━━━━━━━━━━\n\nNo Transaksi : {{invoice}}\nTanggal      : {{tanggal}}\nCustomer     : {{nama_customer}}\nKendaraan    : {{jenis_kendaraan}} - {{plat_nomor}}\n\n━━━━━━━━━━━━━━━━━━\n\n🧼 Layanan:\n{{#each items}}\n• {{nama_layanan}} - Rp{{harga}}\n{{/each}}\n\n━━━━━━━━━━━━━━━━━━\nSubtotal : Rp{{subtotal}}\nDiskon   : Rp{{diskon}}\n━━━━━━━━━━━━━━━━━━\n💰 *Total Bayar : Rp{{total}}*\n\nMetode Bayar : {{metode_bayar}}\nStatus       : {{status}}\n\n━━━━━━━━━━━━━━━━━━\nTerima kasih 🙏";
        $atkReceiptTemplate = Setting::firstOrCreate(
            ['key' => 'whatsapp_atk_receipt_template'],
            [
                'value' => $defaultAtkReceipt,
                'group' => 'whatsapp',
                'type' => 'textarea',
                'label' => 'ATK Receipt Template',
            ]
        );
        $washReceiptTemplate = Setting::firstOrCreate(
            ['key' => 'whatsapp_wash_receipt_template'],
            [
                'value' => $defaultWashReceipt,
                'group' => 'whatsapp',
                'type' => 'textarea',
                'label' => 'Wash Receipt Template',
            ]
        );

        $defaultAtkInvoicePdf = "🧾 *INVOICE PEMBELIAN*\n\nHalo {{nama_customer}},\n\nInvoice {{invoice}} sebesar Rp{{grand_total}}.\n\nDownload nota PDF di sini:\n{{link_pdf}}\n\nTerima kasih 🙏";
        $defaultWashReady = "🚗 *KENDARAAN SELESAI DICUCI*\n\nHalo {{nama_customer}},\n\nKendaraan Anda:\n{{jenis_kendaraan}} - {{plat_nomor}}\n\nSudah selesai dan siap diambil 🙏\n\nTotal pembayaran: Rp{{total}}\n\nTerima kasih sudah menggunakan layanan kami.";
        $atkInvoicePdfTemplate = Setting::firstOrCreate(
            ['key' => 'whatsapp_atk_invoice_pdf_template'],
            [
                'value' => $defaultAtkInvoicePdf,
                'group' => 'whatsapp',
                'type' => 'textarea',
                'label' => 'ATK Invoice PDF Template',
            ]
        );
        $washReadyTemplate = Setting::firstOrCreate(
            ['key' => 'whatsapp_wash_ready_template'],
            [
                'value' => $defaultWashReady,
                'group' => 'whatsapp',
                'type' => 'textarea',
                'label' => 'Wash Ready Template',
            ]
        );

        $defaultIspBill = "🌐 *TAGIHAN INTERNET BULANAN*\n\nHalo {{nama_customer}},\n\nBerikut detail tagihan Anda:\n\nID Pelanggan : {{customer_id}}\nPeriode      : {{periode}}\n\n━━━━━━━━━━━━━━━━━━\nPaket        : {{nama_paket}}\nBiaya Paket  : Rp{{harga_paket}}\nAdmin        : Rp{{biaya_admin}}\n━━━━━━━━━━━━━━━━━━\n💰 *Total Tagihan : Rp{{total}}*\n\nJatuh Tempo  : {{jatuh_tempo}}\nStatus       : {{status}}\n\nSilakan lakukan pembayaran sebelum jatuh tempo 🙏";
        $defaultIspReminder = "⏰ *PENGINGAT TAGIHAN INTERNET*\n\nHalo {{nama_customer}},\n\nTagihan internet Anda sebesar\nRp{{total}}\n\nAkan jatuh tempo pada:\n{{jatuh_tempo}}\n\nMohon segera lakukan pembayaran agar layanan tetap aktif 🙏";
        $defaultIspPaid = "✅ *PEMBAYARAN DITERIMA*\n\nHalo {{nama_customer}},\n\nPembayaran internet periode {{periode}}\nsebesar Rp{{total}}\nteleh kami terima.\n\nStatus layanan: AKTIF\n\nTerima kasih atas kepercayaannya 🙏";
        $defaultIspSuspend = "⚠ *PEMBERITAHUAN LAYANAN*\n\nHalo {{nama_customer}},\n\nKarena belum ada pembayaran hingga melewati jatuh tempo,\nlayanan internet Anda saat ini dinonaktifkan sementara.\n\nTotal tunggakan: Rp{{total}}\n\nSilakan lakukan pembayaran agar layanan kembali aktif.";
        $ispBillTemplate = Setting::firstOrCreate(
            ['key' => 'whatsapp_isp_bill_template'],
            [
                'value' => $defaultIspBill,
                'group' => 'whatsapp',
                'type' => 'textarea',
                'label' => 'ISP Monthly Bill Template',
            ]
        );
        $ispReminderTemplate = Setting::firstOrCreate(
            ['key' => 'whatsapp_isp_reminder_template'],
            [
                'value' => $defaultIspReminder,
                'group' => 'whatsapp',
                'type' => 'textarea',
                'label' => 'ISP Reminder Template',
            ]
        );
        $ispPaidTemplate = Setting::firstOrCreate(
            ['key' => 'whatsapp_isp_payment_success_template'],
            [
                'value' => $defaultIspPaid,
                'group' => 'whatsapp',
                'type' => 'textarea',
                'label' => 'ISP Payment Success Template',
            ]
        );
        $ispSuspendTemplate = Setting::firstOrCreate(
            ['key' => 'whatsapp_isp_suspend_template'],
            [
                'value' => $defaultIspSuspend,
                'group' => 'whatsapp',
                'type' => 'textarea',
                'label' => 'ISP Suspend Template',
            ]
        );
        
        // New setting: Unknown keyword reply
        $defaultUnknownKeywordReply = "Maaf, saya tidak memahami pesan Anda. Silakan ketik \"bantuan\" untuk melihat daftar menu yang tersedia.";
        $unknownKeywordReply = Setting::firstOrCreate(
            ['key' => 'whatsapp_unknown_keyword_reply'],
            [
                'value' => $defaultUnknownKeywordReply,
                'group' => 'whatsapp',
                'type' => 'textarea',
                'label' => 'Balasan untuk Keyword Tidak Dikenali',
            ]
        );

        $waApiUrl = Setting::firstOrCreate(
            ['key' => 'whatsapp_api_url'],
            [
                'value' => env('WHATSAPP_API_URL'),
                'group' => 'whatsapp',
                'type' => 'text',
                'label' => 'WhatsApp API URL',
            ]
        );
        $waApiKey = Setting::firstOrCreate(
            ['key' => 'whatsapp_api_key'],
            [
                'value' => env('WHATSAPP_API_KEY'),
                'group' => 'whatsapp',
                'type' => 'password',
                'label' => 'WhatsApp API Key',
            ]
        );
        
        $waSecretKey = Setting::firstOrCreate(
            ['key' => 'whatsapp_secret_key'],
            [
                'value' => env('WHATSAPP_SECRET_KEY', ''),
                'group' => 'whatsapp',
                'type' => 'password',
                'label' => 'WABLAS Secret Key',
            ]
        );

        // New Group Notification Settings
        Setting::firstOrCreate(['key' => 'whatsapp_ticket_notification_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean', 'label' => 'WhatsApp Ticket Notification Enabled']);
        Setting::firstOrCreate(['key' => 'whatsapp_attendance_notification_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean', 'label' => 'WhatsApp Attendance Notification Enabled']);
        Setting::firstOrCreate(['key' => 'whatsapp_modem_up_notification_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean', 'label' => 'WhatsApp Modem UP Notification Enabled']);
        Setting::firstOrCreate(['key' => 'whatsapp_modem_down_notification_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean', 'label' => 'WhatsApp Modem DOWN Notification Enabled']);
        Setting::firstOrCreate(['key' => 'whatsapp_modem_recap_notification_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean', 'label' => 'WhatsApp Modem RECAP Notification Enabled']);
        Setting::firstOrCreate(['key' => 'whatsapp_autoreply_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean', 'label' => 'WhatsApp Auto Reply Enabled']);
        
        Setting::firstOrCreate(['key' => 'whatsapp_ticket_group_id'], ['value' => Setting::getValue('whatsapp_group_notification_id', ''), 'group' => 'whatsapp', 'type' => 'text', 'label' => 'WhatsApp Ticket Group ID']);
        Setting::firstOrCreate(['key' => 'whatsapp_attendance_group_id'], ['value' => Setting::getValue('whatsapp_group_notification_id', ''), 'group' => 'whatsapp', 'type' => 'text', 'label' => 'WhatsApp Attendance Group ID']);
        Setting::firstOrCreate(['key' => 'whatsapp_modem_up_group_id'], ['value' => Setting::getValue('whatsapp_group_notification_id', ''), 'group' => 'whatsapp', 'type' => 'text', 'label' => 'WhatsApp Modem UP Group ID']);
        Setting::firstOrCreate(['key' => 'whatsapp_modem_down_group_id'], ['value' => Setting::getValue('whatsapp_group_notification_id', ''), 'group' => 'whatsapp', 'type' => 'text', 'label' => 'WhatsApp Modem DOWN Group ID']);
        Setting::firstOrCreate(['key' => 'whatsapp_modem_recap_group_id'], ['value' => Setting::getValue('whatsapp_group_notification_id', ''), 'group' => 'whatsapp', 'type' => 'text', 'label' => 'WhatsApp Modem RECAP Group ID']);

        // Pass masked API keys
        $maskedWaApiKey = is_string($waApiKey->value) && $waApiKey->value !== ''
            ? str_repeat('*', max(strlen($waApiKey->value) - 4, 0)) . substr($waApiKey->value, -4)
            : null;
        
        return view('whatsapp.index', compact(
            'template',
            'atkReceiptTemplate',
            'washReceiptTemplate',
            'atkInvoicePdfTemplate',
            'washReadyTemplate',
            'ispBillTemplate',
            'ispReminderTemplate',
            'ispPaidTemplate',
            'ispSuspendTemplate',
            'unknownKeywordReply',
            'waApiUrl',
            'waApiKey',
            'maskedWaApiKey'
        ));
    }

    /**
     * Update the WhatsApp settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'whatsapp_ticket_template' => 'nullable|string',
            'whatsapp_atk_receipt_template' => 'nullable|string',
            'whatsapp_wash_receipt_template' => 'nullable|string',
            'whatsapp_atk_invoice_pdf_template' => 'nullable|string',
            'whatsapp_wash_ready_template' => 'nullable|string',
            'whatsapp_isp_bill_template' => 'nullable|string',
            'whatsapp_isp_reminder_template' => 'nullable|string',
            'whatsapp_isp_payment_success_template' => 'nullable|string',
            'whatsapp_isp_suspend_template' => 'nullable|string',
            'whatsapp_unknown_keyword_reply' => 'nullable|string',
            'whatsapp_api_url' => 'nullable|string',
            'whatsapp_api_key' => 'nullable|string',
            'whatsapp_secret_key' => 'nullable|string',
            'whatsapp_ticket_group_id' => 'nullable|string',
            'whatsapp_attendance_group_id' => 'nullable|string',
            'whatsapp_modem_up_group_id' => 'nullable|string',
            'whatsapp_modem_down_group_id' => 'nullable|string',
            'whatsapp_modem_recap_group_id' => 'nullable|string',
            'whatsapp_ticket_notification_enabled' => 'nullable|in:0,1',
            'whatsapp_attendance_notification_enabled' => 'nullable|in:0,1',
            'whatsapp_modem_up_notification_enabled' => 'nullable|in:0,1',
            'whatsapp_modem_down_notification_enabled' => 'nullable|in:0,1',
            'whatsapp_modem_recap_notification_enabled' => 'nullable|in:0,1',
            'whatsapp_autoreply_enabled' => 'nullable|in:0,1',
            'duitku_merchant_code' => 'nullable|string',
            'duitku_api_key' => 'nullable|string',
            'duitku_sandbox' => 'nullable|in:0,1',
        ]);

        $groupSettings = [
            'whatsapp_ticket_group_id' => 'WhatsApp Ticket Group ID',
            'whatsapp_attendance_group_id' => 'WhatsApp Attendance Group ID',
            'whatsapp_modem_up_group_id' => 'WhatsApp Modem UP Group ID',
            'whatsapp_modem_down_group_id' => 'WhatsApp Modem DOWN Group ID',
            'whatsapp_modem_recap_group_id' => 'WhatsApp Modem RECAP Group ID',
            'whatsapp_ticket_notification_enabled' => 'WhatsApp Ticket Notification Enabled',
            'whatsapp_attendance_notification_enabled' => 'WhatsApp Attendance Notification Enabled',
            'whatsapp_modem_up_notification_enabled' => 'WhatsApp Modem UP Notification Enabled',
            'whatsapp_modem_down_notification_enabled' => 'WhatsApp Modem DOWN Notification Enabled',
            'whatsapp_modem_recap_notification_enabled' => 'WhatsApp Modem RECAP Notification Enabled',
            'whatsapp_autoreply_enabled' => 'WhatsApp Auto Reply Enabled',
            'whatsapp_delay_reply_enabled' => 'WhatsApp Delay Reply Enabled',
            'whatsapp_delay_reply_minutes' => 'WhatsApp Delay Reply Minutes',
        ];

        foreach ($groupSettings as $key => $label) {
            if ($request->has($key)) {
                $type = str_contains($key, 'enabled') ? 'boolean' : 'text';
                $this->upsertWhatsappSetting($key, $request->input($key), $type, $label);
            }
        }

        if ($request->has('whatsapp_ticket_template')) {
            $this->upsertWhatsappSetting('whatsapp_ticket_template', $request->whatsapp_ticket_template, 'textarea', 'Ticket Notification Template');
        }
        if ($request->has('whatsapp_atk_receipt_template')) {
            $this->upsertWhatsappSetting('whatsapp_atk_receipt_template', $request->whatsapp_atk_receipt_template, 'textarea', 'ATK Receipt Template');
        }
        if ($request->has('whatsapp_wash_receipt_template')) {
            $this->upsertWhatsappSetting('whatsapp_wash_receipt_template', $request->whatsapp_wash_receipt_template, 'textarea', 'Wash Receipt Template');
        }
        $templateLabels = [
            'whatsapp_atk_invoice_pdf_template' => 'ATK Invoice PDF Template',
            'whatsapp_wash_ready_template' => 'Wash Ready Template',
            'whatsapp_isp_bill_template' => 'ISP Monthly Bill Template',
            'whatsapp_isp_reminder_template' => 'ISP Reminder Template',
            'whatsapp_isp_payment_success_template' => 'ISP Payment Success Template',
            'whatsapp_isp_suspend_template' => 'ISP Suspend Template',
            'whatsapp_unknown_keyword_reply' => 'Balasan untuk Keyword Tidak Dikenali',
        ];
        foreach ($templateLabels as $k => $label) {
            if (! $request->has($k)) {
                continue;
            }
            $this->upsertWhatsappSetting($k, (string) $request->input($k), 'textarea', $label);
        }
        if ($request->has('whatsapp_api_url')) {
            $url = trim((string) $request->whatsapp_api_url);
            $url = rtrim($url, '/');
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) === false) {
                return redirect()->back()->withErrors(['whatsapp_api_url' => __('URL WhatsApp API tidak valid')]);
            }
            $this->upsertWhatsappSetting('whatsapp_api_url', $url, 'text', 'WhatsApp API URL');
        }
        if ($request->has('whatsapp_api_key') && $request->whatsapp_api_key !== '') {
            $key = trim((string) $request->whatsapp_api_key);
            $this->upsertWhatsappSetting('whatsapp_api_key', $key, 'password', 'WhatsApp API Key');
        }
        if ($request->has('whatsapp_secret_key')) {
            $key = trim((string) $request->whatsapp_secret_key);
            $this->upsertWhatsappSetting('whatsapp_secret_key', $key, 'password', 'WABLAS Secret Key');
        }

        // Query builder updates do not trigger model events; force refresh cached settings.
        Setting::forgetCache();

        return redirect()->route('whatsapp.index')->with('success', __('WhatsApp settings updated successfully.'));
    }

    /**
     * Send a test message.
     */
    public function test(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string',
            'test_mode' => 'nullable|string',
        ]);

        $whatsappService = app(WhatsAppService::class);
        $phone = preg_replace('/\D+/', '', (string) $request->test_phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        } elseif (! str_starts_with($phone, '62')) {
            $phone = '62'.$phone;
        }
        $mode = $request->input('test_mode', 'plain');
        $message = "*TEST NOTIFICATION*\n\nThis is a test message from your application.\nConnection is successful!";
        if ($mode === 'atk_receipt') {
            $tpl = Setting::getValue('whatsapp_atk_receipt_template');
            if ($tpl) {
                $vars = [
                    'nama_toko' => config('app.name'),
                    'alamat_toko' => Setting::getValue('store_address', 'Jl. Contoh No. 1'),
                    'no_toko' => Setting::getValue('store_phone', '081234567890'),
                    'invoice' => 'ATK-TEST-001',
                    'tanggal' => now()->format('d-m-Y H:i'),
                    'nama_customer' => 'Pelanggan Demo',
                    'subtotal' => number_format(15000, 0, ',', '.'),
                    'diskon' => number_format(0, 0, ',', '.'),
                    'pajak' => number_format(0, 0, ',', '.'),
                    'grand_total' => number_format(15000, 0, ',', '.'),
                    'metode_bayar' => 'CASH',
                    'status' => 'LUNAS',
                    'link_pdf' => url('/demo/atk/invoice.pdf'),
                    'items' => [
                        ['nama_produk' => 'Pulpen', 'qty' => 1, 'harga' => number_format(5000, 0, ',', '.'), 'total' => number_format(5000, 0, ',', '.')],
                        ['nama_produk' => 'Buku Tulis', 'qty' => 1, 'harga' => number_format(10000, 0, ',', '.'), 'total' => number_format(10000, 0, ',', '.')],
                    ],
                ];
                $message = $whatsappService->renderTemplate($tpl, $vars);
            }
        } elseif ($mode === 'wash_receipt') {
            $tpl = Setting::getValue('whatsapp_wash_receipt_template');
            if ($tpl) {
                $vars = [
                    'nama_usaha' => config('app.name'),
                    'alamat' => Setting::getValue('store_address', 'Jl. Contoh No. 1'),
                    'no_hp' => Setting::getValue('store_phone', '081234567890'),
                    'invoice' => 'WASH-TEST-001',
                    'tanggal' => now()->format('d-m-Y H:i'),
                    'nama_customer' => 'Pelanggan Demo',
                    'jenis_kendaraan' => 'Toyota',
                    'plat_nomor' => 'B 1234 CD',
                    'subtotal' => number_format(25000, 0, ',', '.'),
                    'diskon' => number_format(0, 0, ',', '.'),
                    'total' => number_format(25000, 0, ',', '.'),
                    'metode_bayar' => 'CASH',
                    'status' => 'LUNAS',
                    'items' => [
                        ['nama_layanan' => 'Cuci Eksterior', 'harga' => number_format(15000, 0, ',', '.')],
                        ['nama_layanan' => 'Cuci Interior', 'harga' => number_format(10000, 0, ',', '.')],
                    ],
                ];
                $message = $whatsappService->renderTemplate($tpl, $vars);
            }
        } elseif ($mode === 'isp_bill') {
            $tpl = Setting::getValue('whatsapp_isp_bill_template');
            if ($tpl) {
                $vars = [
                    'nama_customer' => 'Pelanggan Demo',
                    'customer_id' => 'CUST-123',
                    'periode' => now()->format('M Y'),
                    'nama_paket' => 'Fiber 50Mbps',
                    'harga_paket' => number_format(200000, 0, ',', '.'),
                    'biaya_admin' => number_format(2500, 0, ',', '.'),
                    'total' => number_format(202500, 0, ',', '.'),
                    'jatuh_tempo' => now()->addDays(7)->format('d-m-Y'),
                    'status' => 'Belum Dibayar',
                ];
                $message = $whatsappService->renderTemplate($tpl, $vars);
            }
        }

        try {
            $result = $whatsappService->sendMessage($phone, $message);
            if (is_array($result) && ($result['success'] ?? false)) {
                return back()->with('success', 'Test message sent successfully!');
            } else {
                $msg = is_array($result) ? ($result['message'] ?? null) : null;
                return back()->with('error', $msg ?: 'Failed to send test message. Check your API Config in .env');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    public function checkStatus(WhatsAppService $whatsappService)
    {
        $status = $whatsappService->checkGatewayStatus();
        $type = $status['ok'] && $status['connected'] ? 'success' : 'error';

        return back()->with($type, $status['message'])->with('wa_gateway_status', $status);
    }

    /**
     * Display WhatsApp logs.
     */
    public function logs(Request $request)
    {
        $type = $request->input('type', 'all');
        $status = $request->input('status', 'all');
        $selectedPhone = $request->input('phone', null);
        
        // Get unique phone numbers for sidebar
        $uniquePhones = WhatsAppLog::select('phone_number')
            ->distinct()
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->pluck('phone_number');
        
        // Get logs
        $logs = WhatsAppLog::orderBy('created_at', 'desc');
        
        if ($type !== 'all') {
            $logs->where('type', $type);
        }
        
        if ($status !== 'all') {
            $logs->where('status', $status);
        }
        
        if ($selectedPhone) {
            $logs->where('phone_number', $selectedPhone);
        }
        
        $logs = $logs->paginate(100)->withQueryString();
        
        // If selected phone, show conversation view
        $conversationView = $selectedPhone ? true : false;
        
        // If conversation view, get all logs for that phone (without pagination)
        $conversation = null;
        if ($selectedPhone) {
            $conversation = WhatsAppLog::where('phone_number', $selectedPhone)
                ->orderBy('created_at', 'asc')
                ->get();
        }
        
        return view('whatsapp.logs', compact('logs', 'type', 'status', 'uniquePhones', 'selectedPhone', 'conversationView', 'conversation'));
    }

    private function upsertWhatsappSetting(string $key, ?string $value, string $type, string $label): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value ?? '',
                'group' => 'whatsapp',
                'type' => $type,
                'label' => $label,
            ]
        );
    }
}
