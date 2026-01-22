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
            new Middleware('permission:telegram.manage', only: ['update', 'test']),
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
                'label' => 'Telegram Bot Token'
            ]
        );

        $groupChatId = Setting::firstOrCreate(
            ['key' => 'telegram_technician_group_chat_id'],
            [
                'value' => '',
                'group' => 'telegram',
                'type' => 'text',
                'label' => 'Technician Group Chat ID'
            ]
        );

        $defaultTemplate = "🔔 *TIKET BARU (NEW TICKET)*\n\n" .
                           "🆔 *No:* `{ticket_number}`\n" .
                           "📝 *Subject:* `{subject}`\n" .
                           "👤 *Customer:* `{customer_name}`\n" .
                           "👷 *Teknisi:* `{technicians}`\n" .
                           "👔 *Koordinator:* `{coordinator}`\n" .
                           "📍 *Lokasi:* `{location}`\n" .
                           "⚠️ *Prioritas:* `{priority}`\n" .
                           "📄 *Deskripsi:* `{description}`\n\n" .
                           "Silakan cek aplikasi untuk detail lebih lanjut.\n" .
                           "[Lihat Lokasi]({location_link})";

        $template = Setting::firstOrCreate(
            ['key' => 'telegram_ticket_template'],
            [
                'value' => $defaultTemplate,
                'group' => 'telegram',
                'type' => 'textarea',
                'label' => 'Ticket Notification Template'
            ]
        );

        return view('telegram.index', compact('setting', 'groupChatId', 'template'));
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
        ]);

        Setting::where('key', 'telegram_bot_token')->update([
            'value' => $request->telegram_bot_token
        ]);

        Setting::where('key', 'telegram_technician_group_chat_id')->update([
            'value' => $request->telegram_technician_group_chat_id
        ]);

        Setting::where('key', 'telegram_ticket_template')->update([
            'value' => $request->telegram_ticket_template
        ]);

        return redirect()->route('telegram.index')->with('success', __('Telegram settings updated successfully.'));
    }

    public function test(Request $request)
    {
        $telegramService = new \App\Services\TelegramService();
        $message = "🔔 *TEST NOTIFICATION*\n\nThis is a test message from your application.\nConnection is successful!";
        
        if ($telegramService->sendToTechnicianGroup($message)) {
            return back()->with('success', 'Test message sent successfully!');
        } else {
            return back()->with('error', 'Failed to send test message. Check your Token and Chat ID.');
        }
    }
}
