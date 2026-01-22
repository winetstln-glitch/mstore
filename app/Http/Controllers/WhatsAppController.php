<?php

namespace App\Http\Controllers;

use App\Models\Setting;
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
            new Middleware('permission:setting.update', only: ['update', 'test']),
        ];
    }

    /**
     * Display the WhatsApp settings page.
     */
    public function index()
    {
        $defaultTemplate = "*TUGAS BARU (TICKET ASSIGNED)*\n\n" .
                           "Halo {technician_name},\n" .
                           "Anda telah ditugaskan untuk tiket berikut:\n\n" .
                           "🎫 *No Tiket:* {ticket_number}\n" .
                           "📝 *Subject:* {subject}\n" .
                           "👤 *Customer:* {customer_name}\n" .
                           "📍 *Lokasi:* {location}\n\n" .
                           "Segera proses tiket ini melalui link berikut:\n{url}";

        $template = Setting::firstOrCreate(
            ['key' => 'whatsapp_ticket_template'],
            [
                'value' => $defaultTemplate,
                'group' => 'whatsapp',
                'type' => 'textarea',
                'label' => 'Ticket Notification Template'
            ]
        );

        return view('whatsapp.index', compact('template'));
    }

    /**
     * Update the WhatsApp settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'whatsapp_ticket_template' => 'nullable|string',
        ]);

        Setting::where('key', 'whatsapp_ticket_template')->update([
            'value' => $request->whatsapp_ticket_template
        ]);

        return redirect()->route('whatsapp.index')->with('success', __('WhatsApp settings updated successfully.'));
    }

    /**
     * Send a test message.
     */
    public function test(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string',
        ]);

        $whatsappService = new \App\Services\WhatsAppService();
        $message = "*TEST NOTIFICATION*\n\nThis is a test message from your application.\nConnection is successful!";
        
        try {
            if ($whatsappService->sendMessage($request->test_phone, $message)) {
                return back()->with('success', 'Test message sent successfully!');
            } else {
                return back()->with('error', 'Failed to send test message. Check your API Config in .env');
            }
        } catch (\Exception $e) {
             return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
