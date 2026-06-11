<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransGateway extends BaseGateway
{
    public function getName(): string
    {
        return 'Midtrans';
    }

    public function getIdentifier(): string
    {
        return 'midtrans';
    }

    public function getConfigKeys(): array
    {
        return ['server_key', 'client_key', 'sandbox'];
    }

    public function testConnection(): array
    {
        try {
            $isSandbox = (bool) ($this->config['sandbox'] ?? true);
            $baseUrl = $isSandbox ? 'https://api.sandbox.midtrans.com/v2' : 'https://api.midtrans.com/v2';
            
            $response = Http::withBasicAuth($this->config['server_key'] ?? '', '')
                ->get("{$baseUrl}/pay-account");

            if ($response->successful() || $response->status() === 404) {
                // 404 is actually fine because it means we reached the API but didn't provide specific account
                return [
                    'success' => true,
                    'message' => 'Connected Successfully',
                    'merchant_name' => 'Midtrans Merchant',
                    'environment' => $isSandbox ? 'Sandbox' : 'Production'
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid Server Key'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function createTransaction(array $payload): array
    {
        try {
            $isSandbox = (bool) ($this->config['sandbox'] ?? true);
            $baseUrl = $isSandbox ? 'https://app.sandbox.midtrans.com/snap/v1' : 'https://app.midtrans.com/snap/v1';

            $params = [
                'transaction_details' => [
                    'order_id' => $payload['reference_id'],
                    'gross_amount' => (int) $payload['amount'],
                ],
                'customer_details' => [
                    'first_name' => $payload['customer_name'],
                    'email' => $payload['customer_email'],
                    'phone' => $payload['customer_phone'],
                ],
            ];

            $response = Http::withBasicAuth($this->config['server_key'] ?? '', '')
                ->post("{$baseUrl}/transactions", $params);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Midtrans Create Transaction Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function verifySignature(array $payload, string $signature): bool
    {
        $calc = hash('sha512', 
            ($payload['order_id'] ?? '') . 
            ($payload['status_code'] ?? '') . 
            ($payload['gross_amount'] ?? '') . 
            ($this->config['server_key'] ?? '')
        );

        return hash_equals($calc, $signature);
    }

    public function handleNotification(array $payload): array
    {
        // Midtrans notification payload is the input itself
        return $payload;
    }

    public function checkStatus(string $referenceId): array
    {
        try {
            $isSandbox = (bool) ($this->config['sandbox'] ?? true);
            $baseUrl = $isSandbox ? 'https://api.sandbox.midtrans.com/v2' : 'https://api.midtrans.com/v2';
            
            $response = Http::withBasicAuth($this->config['server_key'] ?? '', '')
                ->get("{$baseUrl}/{$referenceId}/status");

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Midtrans Check Status Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getPaymentMethods(): array
    {
        // Midtrans SNAP handles this on their page
        return [];
    }
}
