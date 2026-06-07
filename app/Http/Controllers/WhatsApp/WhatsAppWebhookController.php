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
        // Skip verification entirely
        return response($challenge ?? 'OK', 200);
    }

    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('Received WhatsApp webhook', $payload);

        ProcessIncomingWebhookJob::dispatch($payload);

        return response()->json(['status' => 'ok'], 200);
    }
}
