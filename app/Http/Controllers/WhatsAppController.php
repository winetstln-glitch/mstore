<?php

namespace App\Http\Controllers;

use App\Models\Setting;
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
            new Middleware('permission:setting.view', only: ['index']),
            new Middleware('permission:setting.view', only: ['checkStatus']),
            new Middleware('permission:setting.update', only: ['update', 'test']),
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

        // New Group Notification Settings
        Setting::firstOrCreate(['key' => 'whatsapp_ticket_notification_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean', 'label' => 'WhatsApp Ticket Notification Enabled']);
        Setting::firstOrCreate(['key' => 'whatsapp_attendance_notification_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean', 'label' => 'WhatsApp Attendance Notification Enabled']);
        Setting::firstOrCreate(['key' => 'whatsapp_ticket_group_id'], ['value' => Setting::getValue('whatsapp_group_notification_id', ''), 'group' => 'whatsapp', 'type' => 'text', 'label' => 'WhatsApp Ticket Group ID']);
        Setting::firstOrCreate(['key' => 'whatsapp_attendance_group_id'], ['value' => Setting::getValue('whatsapp_group_notification_id', ''), 'group' => 'whatsapp', 'type' => 'text', 'label' => 'WhatsApp Attendance Group ID']);

        return view('whatsapp.index', compact('template', 'atkReceiptTemplate', 'washReceiptTemplate', 'atkInvoicePdfTemplate', 'washReadyTemplate', 'ispBillTemplate', 'ispReminderTemplate', 'ispPaidTemplate', 'ispSuspendTemplate', 'waApiUrl', 'waApiKey'));
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
            'whatsapp_api_url' => 'nullable|string',
            'whatsapp_api_key' => 'nullable|string',
            'whatsapp_ticket_group_id' => 'nullable|string',
            'whatsapp_attendance_group_id' => 'nullable|string',
            'whatsapp_ticket_notification_enabled' => 'nullable|in:0,1',
            'whatsapp_attendance_notification_enabled' => 'nullable|in:0,1',
        ]);

        $groupSettings = [
            'whatsapp_ticket_group_id' => 'WhatsApp Ticket Group ID',
            'whatsapp_attendance_group_id' => 'WhatsApp Attendance Group ID',
            'whatsapp_ticket_notification_enabled' => 'WhatsApp Ticket Notification Enabled',
            'whatsapp_attendance_notification_enabled' => 'WhatsApp Attendance Notification Enabled',
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
            if ($whatsappService->sendMessage($phone, $message)) {
                return back()->with('success', 'Test message sent successfully!');
            } else {
                return back()->with('error', 'Failed to send test message. Check your API Config in .env');
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
