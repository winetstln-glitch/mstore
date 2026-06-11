<?php

namespace App\Services\Payment;

use Duitku\Api;
use Duitku\Config;
use Duitku\Pop;
use Illuminate\Support\Facades\Log;

class DuitkuGateway extends BaseGateway
{
    protected Config $duitkuConfig;

    protected function loadConfig(): void
    {
        parent::loadConfig();

        $this->duitkuConfig = new Config(
            $this->config['api_key'] ?? '',
            $this->config['merchant_code'] ?? ''
        );
        $this->duitkuConfig->setSandboxMode((bool) ($this->config['sandbox'] ?? true));
        $this->duitkuConfig->setSanitizedMode(false);
        $this->duitkuConfig->setDuitkuLogs(false);
    }

    public function getName(): string
    {
        return 'Duitku';
    }

    public function getIdentifier(): string
    {
        return 'duitku';
    }

    public function getConfigKeys(): array
    {
        return ['merchant_code', 'api_key', 'sandbox'];
    }

    public function testConnection(): array
    {
        try {
            // Duitku doesn't have a direct "ping" API, but we can try to fetch payment methods
            $response = Pop::getPaymentMethod(10000, $this->duitkuConfig);
            $data = json_decode($response, true);

            if (isset($data['paymentFee'])) {
                return [
                    'success' => true,
                    'message' => 'Connected Successfully',
                    'merchant_name' => $this->config['merchant_code'] ?? 'Unknown',
                    'environment' => ($this->config['sandbox'] ?? true) ? 'Sandbox' : 'Production'
                ];
            }

            return [
                'success' => false,
                'message' => $data['Message'] ?? 'Invalid Merchant Code or API Key'
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
            $params = [
                'paymentAmount' => $payload['amount'],
                'merchantOrderId' => $payload['reference_id'],
                'productDetails' => $payload['description'],
                'email' => $payload['customer_email'],
                'phoneNumber' => $payload['customer_phone'],
                'customerVaName' => $payload['customer_name'],
                'callbackUrl' => route('voucher.payment.callback'),
                'returnUrl' => route('voucher.payment.return'),
                'expiryPeriod' => 1440,
            ];

            if (isset($payload['payment_method'])) {
                $params['paymentMethod'] = $payload['payment_method'];
                $response = Api::createInvoice($params, $this->duitkuConfig);
            } else {
                $response = Pop::createInvoice($params, $this->duitkuConfig);
            }

            return json_decode($response, true);
        } catch (\Exception $e) {
            Log::error('Duitku Create Transaction Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function verifySignature(array $payload, string $signature): bool
    {
        // Duitku signature is verified via callback() method which handles internally
        return true; 
    }

    public function handleNotification(array $payload): array
    {
        try {
            $callback = Pop::callback($this->duitkuConfig);
            return json_decode($callback, true);
        } catch (\Exception $e) {
            Log::error('Duitku Notification Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function checkStatus(string $referenceId): array
    {
        try {
            $response = Pop::transactionStatus($referenceId, $this->duitkuConfig);
            return json_decode($response, true);
        } catch (\Exception $e) {
            Log::error('Duitku Check Status Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getPaymentMethods(): array
    {
        try {
            $response = Pop::getPaymentMethod(10000, $this->duitkuConfig);
            $data = json_decode($response, true);
            return $data['paymentFee'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
