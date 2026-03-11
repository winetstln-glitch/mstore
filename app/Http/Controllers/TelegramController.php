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

        $defaultTemplate = "🔔 *TIKET BARU (NEW TICKET)*\n\n".
                           "🆔 *No:* `{ticket_number}`\n".
                           "📝 *Subject:* `{subject}`\n".
                           "👤 *Customer:* `{customer_name}`\n".
                           "👷 *Teknisi:* `{technicians}`\n".
                           "👔 *Koordinator:* `{coordinator}`\n".
                           "📍 *Lokasi:* `{location}`\n".
                           "⚠️ *Prioritas:* `{priority}`\n".
                           "📄 *Deskripsi:* `{description}`\n\n".
                           "Silakan cek aplikasi untuk detail lebih lanjut.\n".
                           '[Lihat Lokasi]({location_link})';

        $template = Setting::firstOrCreate(
            ['key' => 'telegram_ticket_template'],
            [
                'value' => $defaultTemplate,
                'group' => 'telegram',
                'type' => 'textarea',
                'label' => 'Ticket Notification Template',
            ]
        );

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
            "*ConnectionRequestURL:* {connection_request_url}\n".
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
            "*ConnectionRequestURL:* {connection_request_url}\n".
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

        return view('telegram.index', compact('setting', 'groupChatId', 'template', 'notifyIpDown', 'notifyIpUp', 'ipDownTemplate', 'ipUpTemplate'));
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
        ]);

        Setting::where('key', 'telegram_bot_token')->update([
            'value' => $request->telegram_bot_token,
        ]);

        Setting::where('key', 'telegram_technician_group_chat_id')->update([
            'value' => $request->telegram_technician_group_chat_id,
        ]);

        Setting::where('key', 'telegram_ticket_template')->update([
            'value' => $request->telegram_ticket_template,
        ]);

        Setting::where('key', 'telegram_notify_ip_down')->update([
            'value' => $request->boolean('telegram_notify_ip_down') ? '1' : '0',
        ]);

        Setting::where('key', 'telegram_notify_ip_up')->update([
            'value' => $request->boolean('telegram_notify_ip_up') ? '1' : '0',
        ]);

        Setting::where('key', 'telegram_ip_down_template')->update([
            'value' => $request->telegram_ip_down_template,
        ]);

        Setting::where('key', 'telegram_ip_up_template')->update([
            'value' => $request->telegram_ip_up_template,
        ]);

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
            "*ConnectionRequestURL:* {connection_request_url}\n".
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
        ]);

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
            "*ConnectionRequestURL:* {connection_request_url}\n".
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
        ]);

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
            "*ConnectionRequestURL:* {connection_request_url}\n".
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
            'tr069_ip' => '10.10.10.12',
            'connection_request_url' => 'http://10.10.10.12:7547/',
            'last_inform' => now()->format('d M Y H:i:s'),
            'reason' => 'Simulasi Preview ONU Offline',
        ]);

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
            "*ConnectionRequestURL:* {connection_request_url}\n".
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
            'tr069_ip' => '10.10.10.13',
            'connection_request_url' => 'http://10.10.10.13:7547/',
            'last_inform' => now()->format('d M Y H:i:s'),
            'reason' => 'Simulasi Preview ONU Recovery',
        ]);

        return back()->with('preview_ip_up', $preview);
    }

    protected function renderTemplate(string $template, array $data): string
    {
        $rendered = $template;
        foreach ($data as $key => $value) {
            $rendered = str_replace('{'.$key.'}', (string) $value, $rendered);
        }

        return $rendered;
    }
}
