<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TelegramController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:telegram.view', only: ['index']),
            new Middleware('permission:telegram.manage', only: ['update', 'test', 'testIpDown', 'testIpUp', 'previewIpDown', 'previewIpUp']),
        ];
    }

    /**
     * Display the Telegram settings page.
     */
    public function index()
    {
        $setting = Setting::firstOrCreate(
            ['key' => 'telegram_bot_token'],
            [
                'value' => env('TELEGRAM_BOT_TOKEN', ''),
                'group' => 'telegram',
                'type' => 'text',
                'label' => 'Telegram Bot Token',
            ]
        );

        $groupChatId = Setting::firstOrCreate(
            ['key' => 'telegram_technician_group_chat_id'],
            [
                'value' => '',
                'group' => 'telegram',
                'type' => 'text',
                'label' => 'Technician Group Chat ID',
            ]
        );

        // New Group Notification Settings
        Setting::firstOrCreate(['key' => 'telegram_ticket_notification_enabled'], ['value' => '1', 'group' => 'telegram', 'type' => 'boolean', 'label' => 'Telegram Ticket Notification Enabled']);
        Setting::firstOrCreate(['key' => 'telegram_attendance_notification_enabled'], ['value' => '1', 'group' => 'telegram', 'type' => 'boolean', 'label' => 'Telegram Attendance Notification Enabled']);
        Setting::firstOrCreate(['key' => 'telegram_modem_up_notification_enabled'], ['value' => '1', 'group' => 'telegram', 'type' => 'boolean', 'label' => 'Telegram Modem UP Notification Enabled']);
        Setting::firstOrCreate(['key' => 'telegram_modem_down_notification_enabled'], ['value' => '1', 'group' => 'telegram', 'type' => 'boolean', 'label' => 'Telegram Modem DOWN Notification Enabled']);
        Setting::firstOrCreate(['key' => 'telegram_modem_recap_notification_enabled'], ['value' => '1', 'group' => 'telegram', 'type' => 'boolean', 'label' => 'Telegram Modem RECAP Notification Enabled']);
        Setting::firstOrCreate(['key' => 'telegram_router_notification_enabled'], ['value' => '1', 'group' => 'telegram', 'type' => 'boolean', 'label' => 'Telegram Router Notification Enabled']);
        
        Setting::firstOrCreate(['key' => 'telegram_ticket_group_id'], ['value' => $groupChatId->value, 'group' => 'telegram', 'type' => 'text', 'label' => 'Telegram Ticket Group ID']);
        Setting::firstOrCreate(['key' => 'telegram_attendance_group_id'], ['value' => $groupChatId->value, 'group' => 'telegram', 'type' => 'text', 'label' => 'Telegram Attendance Group ID']);
        Setting::firstOrCreate(['key' => 'telegram_modem_up_group_id'], ['value' => $groupChatId->value, 'group' => 'telegram', 'type' => 'text', 'label' => 'Telegram Modem UP Group ID']);
        Setting::firstOrCreate(['key' => 'telegram_modem_down_group_id'], ['value' => $groupChatId->value, 'group' => 'telegram', 'type' => 'text', 'label' => 'Telegram Modem DOWN Group ID']);
        Setting::firstOrCreate(['key' => 'telegram_modem_recap_group_id'], ['value' => $groupChatId->value, 'group' => 'telegram', 'type' => 'text', 'label' => 'Telegram Modem RECAP Group ID']);
        Setting::firstOrCreate(['key' => 'telegram_router_group_id'], ['value' => $groupChatId->value, 'group' => 'telegram', 'type' => 'text', 'label' => 'Telegram Router Group ID']);

        // New ticket templates (from WhatsAppController)
        $templates = [
            'ticket_created_telegram_template' => [
                'default' => "🎫 <b>TIKET BARU: {ticket_number}</b>\n\n" .
                    "📌 <b>Tipe:</b> {ticket_type}\n" .
                    "👤 <b>Pelanggan:</b> {customer_name}\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "⚡ <b>Prioritas:</b> {ticket_priority}\n" .
                    "📍 <b>Alamat:</b> {ticket_address}\n\n" .
                    "🔗 <b>Detail:</b> {ticket_url}\n\n" .
                    "🚀 <i>Sistem M-Store</i>",
                'label' => 'Template Notifikasi Telegram: Tiket Baru (Grup)',
            ],
            'ticket_assigned_telegram_template' => [
                'default' => "<b>TUGAS BARU (TICKET ASSIGNED)</b>\n\n" .
                    "Halo {technician_name},\n" .
                    "Anda telah ditugaskan untuk tiket berikut:\n\n" .
                    "🎫 <b>No Tiket:</b> {ticket_number}\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "👤 <b>Customer:</b> {customer_name}\n" .
                    "📍 <b>Lokasi:</b> {ticket_location}\n" .
                    "⚡ <b>Prioritas:</b> {ticket_priority}\n" .
                    "📄 <b>Deskripsi:</b> {ticket_description}\n\n" .
                    "Segera proses tiket ini melalui link berikut:\n{ticket_url}",
                'label' => 'Template Notifikasi Telegram: Tiket Diberikan (Teknisi)',
            ],
            'ticket_status_updated_telegram_template' => [
                'default' => "🔄 <b>STATUS TIKET DIPERBARUI</b>\n\n" .
                    "🎫 <b>No Tiket:</b> {ticket_number}\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "🔄 <b>Status Baru:</b> {new_status}\n" .
                    "👤 <b>Diperbarui Oleh:</b> {updated_by}\n\n" .
                    "🔗 <b>Detail:</b> {ticket_url}",
                'label' => 'Template Notifikasi Telegram: Status Tiket Diperbarui',
            ],
            'ticket_solved_telegram_template' => [
                'default' => "✅ <b>TIKET SELESAI: {ticket_number}</b>\n\n" .
                    "👤 <b>Pelanggan:</b> {customer_name}\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "🛠️ <b>Oleh:</b> {updated_by}\n" .
                    "🗒️ <b>Hasil:</b> {ticket_note}\n\n" .
                    "🚀 <i>Sistem M-Store</i>",
                'label' => 'Template Notifikasi Telegram: Tiket Selesai (Grup)',
            ],
            'ticket_assigned_group_telegram_template' => [
                'default' => "🎫 <b>PENUGASAN TIKET: {ticket_number}</b>\n\n" .
                    "📝 <b>Subjek:</b> {ticket_subject}\n" .
                    "👷 <b>Teknisi:</b> {technician_names}\n" .
                    "👤 <b>Oleh:</b> {updated_by}\n" .
                    "🔗 <b>Detail:</b> {ticket_url}\n\n" .
                    "🚀 <i>Sistem M-Store</i>",
                'label' => 'Template Notifikasi Telegram: Teknisi Ditugaskan (Grup)',
            ],
        ];

        $templateObjects = [];
        foreach ($templates as $key => $config) {
            $templateObjects[$key] = Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $config['default'],
                    'group' => 'notifications',
                    'type' => 'textarea',
                    'label' => $config['label'],
                ]
            );
            if (blank($templateObjects[$key]->value)) {
                $templateObjects[$key]->value = $config['default'];
                $templateObjects[$key]->save();
            }
        }

        $notifyIpDown = Setting::firstOrCreate(
            ['key' => 'telegram_notify_ip_down'],
            [
                'value' => '1',
                'group' => 'telegram',
                'type' => 'boolean',
                'label' => 'Notify IP Down',
            ]
        );

        $notifyIpUp = Setting::firstOrCreate(
            ['key' => 'telegram_notify_ip_up'],
            [
                'value' => '1',
                'group' => 'telegram',
                'type' => 'boolean',
                'label' => 'Notify IP Up',
            ]
        );

        $defaultIpDownTemplate = "🚨 *ALERT MONITORING GENIEACS*\n\n".
            "*Pelanggan:* {customer_name}\n".
            "*Customer ID:* `{customer_id}`\n".
            "*SN ONU:* `{onu_serial}`\n".
            "*Status:* 🔴 OFFLINE\n".
            "*IP TR069:* {tr069_ip}\n".
            "*Connection Request URL:* {connection_request_url}\n".
            "*Terakhir Inform:* {last_inform}\n".
            '*Reason:* {reason}';

        $ipDownTemplate = Setting::firstOrCreate(
            ['key' => 'telegram_ip_down_template'],
            [
                'value' => $defaultIpDownTemplate,
                'group' => 'telegram',
                'type' => 'textarea',
                'label' => 'IP Down Notification Template',
            ]
        );

        $defaultIpUpTemplate = "✅ *RECOVERY MONITORING GENIEACS*\n\n".
            "*Pelanggan:* {customer_name}\n".
            "*Customer ID:* `{customer_id}`\n".
            "*SN ONU:* `{onu_serial}`\n".
            "*Status:* 🟢 ONLINE\n".
            "*IP TR069:* {tr069_ip}\n".
            "*Connection Request URL:* {connection_request_url}\n".
            "*Terakhir Inform:* {last_inform}\n".
            '*Reason:* {reason}';

        $ipUpTemplate = Setting::firstOrCreate(
            ['key' => 'telegram_ip_up_template'],
            [
                'value' => $defaultIpUpTemplate,
                'group' => 'telegram',
                'type' => 'textarea',
                'label' => 'IP Up Notification Template',
            ]
        );

        $onlineThresholdMinutes = Setting::firstOrCreate(
            ['key' => 'genieacs_online_threshold_minutes'],
            [
                'value' => '5',
                'group' => 'telegram',
                'type' => 'number',
                'label' => 'Batas Online Last Inform (menit)',
            ]
        );

        $downConfirmChecks = Setting::firstOrCreate(
            ['key' => 'network_monitor_down_confirm_checks'],
            [
                'value' => '1',
                'group' => 'telegram',
                'type' => 'number',
                'label' => 'Konfirmasi Down (jumlah cek)',
            ]
        );

        $upConfirmChecks = Setting::firstOrCreate(
            ['key' => 'network_monitor_up_confirm_checks'],
            [
                'value' => '1',
                'group' => 'telegram',
                'type' => 'number',
                'label' => 'Konfirmasi Up (jumlah cek)',
            ]
        );

        $telegramRetryAttempts = Setting::firstOrCreate(
            ['key' => 'network_monitor_telegram_max_retry_attempts'],
            [
                'value' => '5',
                'group' => 'telegram',
                'type' => 'number',
                'label' => 'Maks Retry Telegram',
            ]
        );

        $telegramRetryBackoffMinutes = Setting::firstOrCreate(
            ['key' => 'network_monitor_telegram_retry_backoff_minutes'],
            [
                'value' => '5',
                'group' => 'telegram',
                'type' => 'number',
                'label' => 'Jeda Retry Telegram (menit)',
            ]
        );

        $telegramMonitorDetailLimit = Setting::firstOrCreate(
            ['key' => 'telegram_monitor_detail_list_limit'],
            [
                'value' => '20',
                'group' => 'telegram',
                'type' => 'number',
                'label' => 'Maks data detail ONLINE/OFFLINE',
            ]
        );

        // Get router notification settings
        $notifyRouter = Setting::firstOrCreate(
            ['key' => 'telegram_router_notification_enabled'],
            ['value' => '1', 'group' => 'telegram', 'type' => 'boolean', 'label' => 'Telegram Router Notification Enabled']
        );
        $routerGroupId = Setting::firstOrCreate(
            ['key' => 'telegram_router_group_id'],
            ['value' => '', 'group' => 'telegram', 'type' => 'text', 'label' => 'Telegram Router Group ID']
        );

        return view('telegram.index', array_merge(
            compact(
                'setting',
                'groupChatId',
                'notifyIpDown',
                'notifyIpUp',
                'ipDownTemplate',
                'ipUpTemplate',
                'onlineThresholdMinutes',
                'downConfirmChecks',
                'upConfirmChecks',
                'telegramRetryAttempts',
                'telegramRetryBackoffMinutes',
                'telegramMonitorDetailLimit',
                'notifyRouter',
                'routerGroupId'
            ),
            $templateObjects
        ));
    }

    /**
     * Update the Telegram settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'telegram_bot_token' => 'nullable|string',
            'telegram_technician_group_chat_id' => 'nullable|string',
            'telegram_ticket_template' => 'nullable|string',
            'telegram_notify_ip_down' => 'nullable|boolean',
            'telegram_notify_ip_up' => 'nullable|boolean',
            'telegram_ip_down_template' => 'nullable|string',
            'telegram_ip_up_template' => 'nullable|string',
            'genieacs_online_threshold_minutes' => 'nullable|integer|min:1|max:180',
            'network_monitor_down_confirm_checks' => 'nullable|integer|min:1|max:10',
            'network_monitor_up_confirm_checks' => 'nullable|integer|min:1|max:10',
            'network_monitor_telegram_max_retry_attempts' => 'nullable|integer|min:1|max:20',
            'network_monitor_telegram_retry_backoff_minutes' => 'nullable|integer|min:1|max:120',
            'telegram_monitor_detail_list_limit' => 'nullable|integer|min:5|max:100',
            'telegram_router_notification_enabled' => 'nullable|boolean',
            'telegram_router_group_id' => 'nullable|string',
            // New ticket templates
            'ticket_created_telegram_template' => 'nullable|string',
            'ticket_assigned_telegram_template' => 'nullable|string',
            'ticket_status_updated_telegram_template' => 'nullable|string',
            'ticket_solved_telegram_template' => 'nullable|string',
            'ticket_assigned_group_telegram_template' => 'nullable|string',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_bot_token'], [
            'value' => $request->telegram_bot_token,
            'group' => 'telegram',
            'type' => 'text',
            'label' => 'Telegram Bot Token',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_technician_group_chat_id'], [
            'value' => $request->telegram_technician_group_chat_id,
            'group' => 'telegram',
            'type' => 'text',
            'label' => 'Technician Group Chat ID',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_ticket_group_id'], [
            'value' => $request->telegram_ticket_group_id,
            'group' => 'telegram',
            'type' => 'text',
            'label' => 'Telegram Ticket Group ID',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_attendance_group_id'], [
            'value' => $request->telegram_attendance_group_id,
            'group' => 'telegram',
            'type' => 'text',
            'label' => 'Telegram Attendance Group ID',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_modem_up_group_id'], [
            'value' => $request->telegram_modem_up_group_id,
            'group' => 'telegram',
            'type' => 'text',
            'label' => 'Telegram Modem UP Group ID',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_modem_down_group_id'], [
            'value' => $request->telegram_modem_down_group_id,
            'group' => 'telegram',
            'type' => 'text',
            'label' => 'Telegram Modem DOWN Group ID',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_modem_recap_group_id'], [
            'value' => $request->telegram_modem_recap_group_id,
            'group' => 'telegram',
            'type' => 'text',
            'label' => 'Telegram Modem RECAP Group ID',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_ticket_notification_enabled'], [
            'value' => $request->boolean('telegram_ticket_notification_enabled') ? '1' : '0',
            'group' => 'telegram',
            'type' => 'boolean',
            'label' => 'Telegram Ticket Notification Enabled',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_attendance_notification_enabled'], [
            'value' => $request->boolean('telegram_attendance_notification_enabled') ? '1' : '0',
            'group' => 'telegram',
            'type' => 'boolean',
            'label' => 'Telegram Attendance Notification Enabled',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_modem_up_notification_enabled'], [
            'value' => $request->boolean('telegram_modem_up_notification_enabled') ? '1' : '0',
            'group' => 'telegram',
            'type' => 'boolean',
            'label' => 'Telegram Modem UP Notification Enabled',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_modem_down_notification_enabled'], [
            'value' => $request->boolean('telegram_modem_down_notification_enabled') ? '1' : '0',
            'group' => 'telegram',
            'type' => 'boolean',
            'label' => 'Telegram Modem DOWN Notification Enabled',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_modem_recap_notification_enabled'], [
            'value' => $request->boolean('telegram_modem_recap_notification_enabled') ? '1' : '0',
            'group' => 'telegram',
            'type' => 'boolean',
            'label' => 'Telegram Modem RECAP Notification Enabled',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_router_notification_enabled'], [
            'value' => $request->boolean('telegram_router_notification_enabled') ? '1' : '0',
            'group' => 'telegram',
            'type' => 'boolean',
            'label' => 'Telegram Router Notification Enabled',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_router_group_id'], [
            'value' => $request->telegram_router_group_id,
            'group' => 'telegram',
            'type' => 'text',
            'label' => 'Telegram Router Group ID',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_ticket_template'], [
            'value' => $request->telegram_ticket_template,
            'group' => 'telegram',
            'type' => 'textarea',
            'label' => 'Ticket Notification Template',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_notify_ip_down'], [
            'value' => $request->boolean('telegram_notify_ip_down') ? '1' : '0',
            'group' => 'telegram',
            'type' => 'boolean',
            'label' => 'Notify IP Down',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_notify_ip_up'], [
            'value' => $request->boolean('telegram_notify_ip_up') ? '1' : '0',
            'group' => 'telegram',
            'type' => 'boolean',
            'label' => 'Notify IP Up',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_ip_down_template'], [
            'value' => $request->telegram_ip_down_template,
            'group' => 'telegram',
            'type' => 'textarea',
            'label' => 'IP Down Notification Template',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_ip_up_template'], [
            'value' => $request->telegram_ip_up_template,
            'group' => 'telegram',
            'type' => 'textarea',
            'label' => 'IP Up Notification Template',
        ]);

        Setting::updateOrCreate(['key' => 'genieacs_online_threshold_minutes'], [
            'value' => (string) ((int) $request->input('genieacs_online_threshold_minutes', 5)),
            'group' => 'telegram',
            'type' => 'number',
            'label' => 'Batas Online Last Inform (menit)',
        ]);

        Setting::updateOrCreate(['key' => 'network_monitor_down_confirm_checks'], [
            'value' => (string) ((int) $request->input('network_monitor_down_confirm_checks', 1)),
            'group' => 'telegram',
            'type' => 'number',
            'label' => 'Konfirmasi Down (jumlah cek)',
        ]);

        Setting::updateOrCreate(['key' => 'network_monitor_up_confirm_checks'], [
            'value' => (string) ((int) $request->input('network_monitor_up_confirm_checks', 1)),
            'group' => 'telegram',
            'type' => 'number',
            'label' => 'Konfirmasi Up (jumlah cek)',
        ]);

        Setting::updateOrCreate(['key' => 'network_monitor_telegram_max_retry_attempts'], [
            'value' => (string) ((int) $request->input('network_monitor_telegram_max_retry_attempts', 5)),
            'group' => 'telegram',
            'type' => 'number',
            'label' => 'Maks Retry Telegram',
        ]);

        Setting::updateOrCreate(['key' => 'network_monitor_telegram_retry_backoff_minutes'], [
            'value' => (string) ((int) $request->input('network_monitor_telegram_retry_backoff_minutes', 5)),
            'group' => 'telegram',
            'type' => 'number',
            'label' => 'Jeda Retry Telegram (menit)',
        ]);

        Setting::updateOrCreate(['key' => 'telegram_monitor_detail_list_limit'], [
            'value' => (string) ((int) $request->input('telegram_monitor_detail_list_limit', 20)),
            'group' => 'telegram',
            'type' => 'number',
            'label' => 'Maks data detail ONLINE/OFFLINE',
        ]);

        // Save new ticket templates
        $templateLabels = [
            'ticket_created_telegram_template' => 'Template Notifikasi Telegram: Tiket Baru (Grup)',
            'ticket_assigned_telegram_template' => 'Template Notifikasi Telegram: Tiket Diberikan (Teknisi)',
            'ticket_status_updated_telegram_template' => 'Template Notifikasi Telegram: Status Tiket Diperbarui',
            'ticket_solved_telegram_template' => 'Template Notifikasi Telegram: Tiket Selesai (Grup)',
            'ticket_assigned_group_telegram_template' => 'Template Notifikasi Telegram: Teknisi Ditugaskan (Grup)',
        ];
        foreach ($templateLabels as $key => $label) {
            if ($request->has($key)) {
                Setting::updateOrCreate(['key' => $key], [
                    'value' => $request->input($key),
                    'group' => 'notifications',
                    'type' => 'textarea',
                    'label' => $label,
                ]);
            }
        }

        return redirect()->route('telegram.index')->with('success', __('Telegram settings updated successfully.'));
    }

    public function test(Request $request)
    {
        $telegramService = new \App\Services\TelegramService;
        $message = "🔔 *TEST NOTIFICATION*\n\nThis is a test message from your application.\nConnection is successful!";

        if ($telegramService->sendToTechnicianGroup($message)) {
            return back()->with('success', 'Test message sent successfully!');
        } else {
            return back()->with('error', 'Failed to send test message. Check your Token and Chat ID.');
        }
    }

    public function testIpDown(Request $request)
    {
        $telegramService = new \App\Services\TelegramService;
        $defaultTemplate = "🚨 *ALERT MONITORING GENIEACS*\n\n".
            "*Pelanggan:* {customer_name}\n".
            "*Customer ID:* `{customer_id}`\n".
            "*SN ONU:* `{onu_serial}`\n".
            "*Status:* 🔴 OFFLINE\n".
            "*IP TR069:* {tr069_ip}\n".
            "*Connection Request URL:* {connection_request_url}\n".
            "*Terakhir Inform:* {last_inform}\n".
            '*Reason:* {reason}';
        $template = Setting::getValue('telegram_ip_down_template', $defaultTemplate);
        if (! is_string($template) || trim($template) === '') {
            $template = $defaultTemplate;
        }

        $message = $this->renderTemplate($template, [
            'customer_name' => 'Pelanggan Test DOWN',
            'customer_id' => '99999',
            'onu_serial' => 'TESTDOWN123456',
            'status' => '🔴 OFFLINE',
            'tr069_ip' => '10.10.10.2',
            'connection_request_url' => 'http://10.10.10.2:7547/',
            'last_inform' => now()->format('d M Y H:i:s'),
            'reason' => 'Simulasi ONU Offline',
        ], true);

        if ($telegramService->sendToTechnicianGroup($message)) {
            return back()->with('success', 'Test notifikasi IP DOWN berhasil dikirim.');
        }

        return back()->with('error', 'Gagal kirim test notifikasi IP DOWN. Periksa Token dan Chat ID.');
    }

    public function testIpUp(Request $request)
    {
        $telegramService = new \App\Services\TelegramService;
        $defaultTemplate = "✅ *RECOVERY MONITORING GENIEACS*\n\n".
            "*Pelanggan:* {customer_name}\n".
            "*Customer ID:* `{customer_id}`\n".
            "*SN ONU:* `{onu_serial}`\n".
            "*Status:* 🟢 ONLINE\n".
            "*IP TR069:* {tr069_ip}\n".
            "*Connection Request URL:* {connection_request_url}\n".
            "*Terakhir Inform:* {last_inform}\n".
            '*Reason:* {reason}';
        $template = Setting::getValue('telegram_ip_up_template', $defaultTemplate);
        if (! is_string($template) || trim($template) === '') {
            $template = $defaultTemplate;
        }

        $message = $this->renderTemplate($template, [
            'customer_name' => 'Pelanggan Test UP',
            'customer_id' => '99999',
            'onu_serial' => 'TESTUP123456',
            'status' => '🟢 ONLINE',
            'tr069_ip' => '10.10.10.3',
            'connection_request_url' => 'http://10.10.10.3:7547/',
            'last_inform' => now()->format('d M Y H:i:s'),
            'reason' => 'Simulasi ONU Recovery',
        ], true);

        if ($telegramService->sendToTechnicianGroup($message)) {
            return back()->with('success', 'Test notifikasi IP UP berhasil dikirim.');
        }

        return back()->with('error', 'Gagal kirim test notifikasi IP UP. Periksa Token dan Chat ID.');
    }

    public function previewIpDown(Request $request)
    {
        $defaultTemplate = "🚨 *ALERT MONITORING GENIEACS*\n\n".
            "*Pelanggan:* {customer_name}\n".
            "*Customer ID:* `{customer_id}`\n".
            "*SN ONU:* `{onu_serial}`\n".
            "*Status:* 🔴 OFFLINE\n".
            "*IP TR069:* {tr069_ip}\n".
            "*Connection Request URL:* {connection_request_url}\n".
            "*Terakhir Inform:* {last_inform}\n".
            '*Reason:* {reason}';
        $template = Setting::getValue('telegram_ip_down_template', $defaultTemplate);
        if (! is_string($template) || trim($template) === '') {
            $template = $defaultTemplate;
        }

        $preview = $this->renderTemplate($template, [
            'customer_name' => 'Pelanggan Preview DOWN',
            'customer_id' => '99999',
            'onu_serial' => 'PREVIEWDOWN123456',
            'status' => '🔴 OFFLINE',
            'tr069_ip' => '10.10.10.22',
            'connection_request_url' => 'http://10.10.10.22:7547/',
            'last_inform' => now()->format('d M Y H:i:s'),
            'reason' => 'Preview ONU Offline',
        ], true);

        return back()->with('preview_ip_down', $preview);
    }

    public function previewIpUp(Request $request)
    {
        $defaultTemplate = "✅ *RECOVERY MONITORING GENIEACS*\n\n".
            "*Pelanggan:* {customer_name}\n".
            "*Customer ID:* `{customer_id}`\n".
            "*SN ONU:* `{onu_serial}`\n".
            "*Status:* 🟢 ONLINE\n".
            "*IP TR069:* {tr069_ip}\n".
            "*Connection Request URL:* {connection_request_url}\n".
            "*Terakhir Inform:* {last_inform}\n".
            '*Reason:* {reason}';
        $template = Setting::getValue('telegram_ip_up_template', $defaultTemplate);
        if (! is_string($template) || trim($template) === '') {
            $template = $defaultTemplate;
        }

        $preview = $this->renderTemplate($template, [
            'customer_name' => 'Pelanggan Preview UP',
            'customer_id' => '99999',
            'onu_serial' => 'PREVIEWUP123456',
            'status' => '🟢 ONLINE',
            'tr069_ip' => '10.10.10.33',
            'connection_request_url' => 'http://10.10.10.33:7547/',
            'last_inform' => now()->format('d M Y H:i:s'),
            'reason' => 'Preview ONU Recovery',
        ], true);

        return back()->with('preview_ip_up', $preview);
    }

    protected function renderTemplate(string $template, array $data, bool $escape = false): string
    {
        $rendered = $template;
        foreach ($data as $key => $value) {
            $val = (string) $value;
            if ($escape) {
                $val = \App\Services\TelegramService::escape($val);
            }
            $rendered = str_replace('{'.$key.'}', $val, $rendered);
        }

        return $rendered;
    }
}
