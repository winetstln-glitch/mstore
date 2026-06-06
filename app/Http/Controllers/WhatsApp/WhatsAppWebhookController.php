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
        $mode = $request->input('hub_mode');
        $verifyToken = $request->input('hub_verify_token') ?? $request->input('verify_token');
        $challenge = $request->input('hub_challenge') ?? $request->input('challenge');

        // Skip verification if no token is set
        if (empty(config('services.whatsapp.verify_token'))) {
            Log::info('WhatsApp webhook verification skipped (no token set)');
            return response($challenge ?? 'OK', 200);
        }

        if ($mode === 'subscribe' && $verifyToken === config('services.whatsapp.verify_token')) {
            Log::info('WhatsApp webhook verified');
            return response($challenge, 200);
        }

        // Also check for direct verification without hub_mode
        if ($verifyToken === config('services.whatsapp.verify_token')) {
            Log::info('WhatsApp webhook verified (direct)');
            return response($challenge ?? 'OK', 200);
        }

        Log::warning('Invalid WhatsApp webhook verification', [
            'mode' => $mode,
            'verify_token' => $verifyToken,
        ]);

        abort(403);
    }

    public function handle(WebhookRequest $request)
    {
        $payload = $request->all();

        Log::info('Received WhatsApp webhook', $payload);

        ProcessIncomingWebhookJob::dispatch($payload);

        return response()->json(['status' => 'ok'], 200);
    }
}
