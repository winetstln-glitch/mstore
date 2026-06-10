<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyWhatsAppWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $secret = config('services.whatsapp.secret');
        $signature256 = $request->header('X-Hub-Signature-256');
        $signatureLegacy = $request->header('X-WA-Signature');

        if (! is_string($secret) || trim($secret) === '') {
            if (app()->environment('production')) {
                Log::error('WhatsApp webhook secret is not configured');
                abort(500, 'Webhook secret not configured');
            }
            return $next($request);
        }

        $rawBody = (string) $request->getContent();

        $valid = false;
        if (is_string($signature256) && $signature256 !== '') {
            $valid = $this->verifyHubSignature256($rawBody, $signature256, $secret);
        } elseif (is_string($signatureLegacy) && $signatureLegacy !== '') {
            $valid = $this->verifyLegacySignature($rawBody, $signatureLegacy, $secret);
        }

        if (! $valid) {
            Log::warning('Invalid WhatsApp webhook signature', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            abort(403, 'Invalid signature');
        }

        return $next($request);
    }

    private function verifyHubSignature256(string $payload, string $signatureHeader, string $secret): bool
    {
        $secret = trim($secret);
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signatureHeader);
    }

    private function verifyLegacySignature(string $payload, string $signature, string $secret): bool
    {
        $secret = trim($secret);
        $expectedHash = hash_hmac('sha256', $payload, $secret);
        if (str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, strlen('sha256='));
        }
        return hash_equals($expectedHash, $signature);
    }
}
