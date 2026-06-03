<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DuitkuService
{
    protected $merchantCode;
    protected $apiKey;
    protected $baseUrl;
    protected $callbackUrl;
    protected $returnUrl;

    public function __construct()
    {
        $this->merchantCode = Setting::getValue('duitku_merchant_code', config('services.duitku.merchant_code'));
        $this->apiKey = Setting::getValue('duitku_api_key', config('services.duitku.api_key'));
        $sandbox = Setting::getValue('duitku_sandbox', config('services.duitku.sandbox', true));
        $this->baseUrl = $sandbox ? 'https://sandbox.duitku.com' : 'https://passport.duitku.com';
        $this->callbackUrl = route('voucher.payment.callback');
        $this->returnUrl = route('voucher.payment.return');
    }

    public function createTransaction($referenceId, $amount, $paymentMethod, $productDetails, $customerName, $customerEmail, $customerPhone)
    {
        $timestamp = time();
        $signature = md5($this->merchantCode . $amount . $referenceId . $this->apiKey);

        $payload = [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $amount,
            'merchantOrderId' => $referenceId,
            'productDetails' => $productDetails,
            'customerName' => $customerName,
            'customerEmail' => $customerEmail,
            'customerPhone' => $customerPhone,
            'merchantUserInfo' => $customerPhone,
            'paymentMethod' => $paymentMethod,
            'signature' => $signature,
            'expiryPeriod' => 1440, // 24 hours in minutes
            'callbackUrl' => $this->callbackUrl,
            'returnUrl' => $this->returnUrl,
        ];

        try {
            $response = Http::timeout(30)->post($this->baseUrl . '/webapi/api/merchant/v2/inquiry', $payload);
            $result = $response->json();
            
            Log::info('Duitku Create Transaction Response', ['payload' => $payload, 'response' => $result]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Duitku Create Transaction Error', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function checkTransaction($referenceId)
    {
        $timestamp = time();
        $signature = md5($this->merchantCode . $referenceId . $this->apiKey);

        $payload = [
            'merchantCode' => $this->merchantCode,
            'merchantOrderId' => $referenceId,
            'signature' => $signature,
        ];

        try {
            $response = Http::timeout(30)->post($this->baseUrl . '/webapi/api/merchant/transactionStatus', $payload);
            $result = $response->json();
            
            Log::info('Duitku Check Transaction Response', ['payload' => $payload, 'response' => $result]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Duitku Check Transaction Error', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function verifyCallback($data)
    {
        $signature = md5($this->merchantCode . $data['merchantOrderId'] . $data['statusCode'] . $this->apiKey);
        
        return $signature === $data['signature'];
    }
}
