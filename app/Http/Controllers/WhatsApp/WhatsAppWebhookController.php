<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\WebhookRequest;
use App\Jobs\WhatsApp\ProcessIncomingWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $challenge = $request->input('hub.challenge') ?? $request->input('challenge');
        $verifyToken = config('services.whatsapp.verify_token');
        $receivedToken = $request->input('hub.verify_token') ?? $request->input('verify_token');

        if (app()->environment('production')) {
            if (! is_string($verifyToken) || trim($verifyToken) === '' || $verifyToken === 'your-verify-token-change-me') {
                return response('Webhook verify token is not configured', 500);
            }
        }

        if (is_string($verifyToken) && trim($verifyToken) !== '' && $verifyToken !== 'your-verify-token-change-me') {
            if (! is_string($receivedToken) || $receivedToken === '' || ! hash_equals($verifyToken, $receivedToken)) {
                return response('Invalid verify token', 403);
            }
        }

        return response((string) ($challenge ?? 'OK'), 200);
    }

    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('Received WhatsApp webhook', [
            'keys' => array_slice(array_keys($payload), 0, 25),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        ProcessIncomingWebhookJob::dispatch($payload);

        return response()->json(['status' => 'ok'], 200);
    }
}
