<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class MidtransService
{
    protected string $serverKey;
    protected string $clientKey;
    protected bool $isProduction;

    public function __construct()
    {
        $this->serverKey = (string) config('services.midtrans.server_key', env('MIDTRANS_SERVER_KEY'));
        $this->clientKey = (string) config('services.midtrans.client_key', env('MIDTRANS_CLIENT_KEY'));
        $this->isProduction = filter_var(config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false)), FILTER_VALIDATE_BOOL);
    }

    public function getClientKey(): string
    {
        return $this->clientKey;
    }

    public function createSnapToken(Invoice $invoice): string
    {
        $orderId = $invoice->code ?: ('INV-' . $invoice->id . '-' . Str::random(6));
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $invoice->amount,
            ],
            'customer_details' => $this->customerDetails($invoice->user),
            'item_details' => [
                [
                    'id' => $invoice->id,
                    'price' => (int) $invoice->amount,
                    'quantity' => 1,
                    'name' => 'Invoice ' . $orderId,
                ],
            ],
            'callbacks' => [
                'finish' => url('/client/invoices'),
            ],
        ];

        // Prefer library if available
        if (class_exists('\\Midtrans\\Snap') && class_exists('\\Midtrans\\Config')) {
            \Midtrans\Config::$serverKey = $this->serverKey;
            \Midtrans\Config::$isProduction = $this->isProduction;
            \Midtrans\Config::$isSanitized = true;
            \Midtrans\Config::$is3ds = true;
            $token = \Midtrans\Snap::getSnapToken($params);
        } else {
            // Fallback: call Snap REST
            $base = $this->isProduction ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
            $resp = Http::withBasicAuth($this->serverKey, '')
                ->acceptJson()
                ->post($base . '/snap/v1/transactions', $params)
                ->throw();
            $token = (string) $resp->json('token');
        }

        $invoice->update([
            'code' => $orderId,
            'midtrans_order_id' => $orderId,
            'snap_token' => $token,
        ]);

        return $token;
    }

    public function verifySignature(array $payload): bool
    {
        // signature_key = sha512(order_id + status_code + gross_amount + server_key)
        $calc = hash('sha512', ($payload['order_id'] ?? '') . ($payload['status_code'] ?? '') . ($payload['gross_amount'] ?? '') . $this->serverKey);
        return hash_equals($calc, $payload['signature_key'] ?? '');
    }

    protected function customerDetails(User $user): array
    {
        return [
            'first_name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];
    }
}

