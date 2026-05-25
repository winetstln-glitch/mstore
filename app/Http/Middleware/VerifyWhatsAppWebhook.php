<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyWhatsAppWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $verifyToken = config('services.whatsapp.verify_token');
        $signature = $request->header('X-WA-Signature');

        if (empty($signature) || !$this->verifySignature($request->getContent(), $signature)) {
            Log::warning('Invalid WhatsApp webhook signature', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            abort(403, 'Invalid signature');
        }

        return $next($request);
    }

    private function verifySignature(string $payload, string $signature): bool
    {
        $secret = config('services.whatsapp.secret');
        if (empty($secret)) {
            return true;
        }
        $expectedHash = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedHash, $signature);
    }
}
