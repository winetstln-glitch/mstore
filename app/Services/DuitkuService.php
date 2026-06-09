<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Duitku\Config;
use Duitku\Pop;
use Duitku\Api;

class DuitkuService
{
    protected $duitkuConfig;
    protected $callbackUrl;
    protected $returnUrl;

    public function __construct()
    {
        $merchantCode = Setting::getValue('duitku_merchant_code', config('services.duitku.merchant_code'));
        $apiKey = Setting::getValue('duitku_api_key', config('services.duitku.api_key'));
        $sandbox = Setting::getValue('duitku_sandbox', config('services.duitku.sandbox', true));
        
        $this->duitkuConfig = new Config($apiKey, $merchantCode);
        $this->duitkuConfig->setSandboxMode($sandbox);
        $this->duitkuConfig->setSanitizedMode(false);
        $this->duitkuConfig->setDuitkuLogs(false);
        
        $this->callbackUrl = route('voucher.payment.callback');
        $this->returnUrl = route('voucher.payment.return');
    }

    /**
     * Create transaction using Duitku-POP
     */
    public function createPopTransaction($referenceId, $amount, $productDetails, $customerName, $customerEmail, $customerPhone, $paymentMethod = null)
    {
        try {
            $params = [
                'paymentAmount' => $amount,
                'merchantOrderId' => $referenceId,
                'productDetails' => $productDetails,
                'additionalParam' => '',
                'merchantUserInfo' => $customerPhone,
                'customerVaName' => $customerName,
                'email' => $customerEmail,
                'phoneNumber' => $customerPhone,
                'itemDetails' => [
                    [
                        'name' => $productDetails,
                        'price' => $amount,
                        'quantity' => 1
                    ]
                ],
                'customerDetail' => [
                    'firstName' => $customerName,
                    'lastName' => '',
                    'email' => $customerEmail,
                    'phoneNumber' => $customerPhone
                ],
                'callbackUrl' => $this->callbackUrl,
                'returnUrl' => $this->returnUrl,
                'expiryPeriod' => 1440,
            ];

            if ($paymentMethod) {
                $params['paymentMethod'] = $paymentMethod;
            }

            $response = Pop::createInvoice($params, $this->duitkuConfig);
            
            Log::info('Duitku-POP Create Transaction Response', ['params' => $params, 'response' => $response]);
            
            return json_decode($response, true);
        } catch (\Exception $e) {
            Log::error('Duitku-POP Create Transaction Error', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create transaction using Duitku-API (direct)
     */
    public function createApiTransaction($referenceId, $amount, $paymentMethod, $productDetails, $customerName, $customerEmail, $customerPhone)
    {
        try {
            $params = [
                'paymentAmount' => $amount,
                'paymentMethod' => $paymentMethod,
                'merchantOrderId' => $referenceId,
                'productDetails' => $productDetails,
                'additionalParam' => '',
                'merchantUserInfo' => $customerPhone,
                'customerVaName' => $customerName,
                'email' => $customerEmail,
                'phoneNumber' => $customerPhone,
                'itemDetails' => [
                    [
                        'name' => $productDetails,
                        'price' => $amount,
                        'quantity' => 1
                    ]
                ],
                'customerDetail' => [
                    'firstName' => $customerName,
                    'lastName' => '',
                    'email' => $customerEmail,
                    'phoneNumber' => $customerPhone
                ],
                'callbackUrl' => $this->callbackUrl,
                'returnUrl' => $this->returnUrl,
                'expiryPeriod' => 1440,
            ];

            $response = Api::createInvoice($params, $this->duitkuConfig);
            
            Log::info('Duitku-API Create Transaction Response', ['params' => $params, 'response' => $response]);
            
            return json_decode($response, true);
        } catch (\Exception $e) {
            Log::error('Duitku-API Create Transaction Error', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check transaction status (works for both POP and API)
     */
    public function checkTransaction($referenceId, $useApi = false)
    {
        try {
            if ($useApi) {
                $response = Api::transactionStatus($referenceId, $this->duitkuConfig);
            } else {
                $response = Pop::transactionStatus($referenceId, $this->duitkuConfig);
            }
            
            Log::info('Duitku Check Transaction Response', ['referenceId' => $referenceId, 'response' => $response]);
            
            return json_decode($response, true);
        } catch (\Exception $e) {
            Log::error('Duitku Check Transaction Error', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethod($amount, $useApi = false)
    {
        try {
            if ($useApi) {
                $response = Api::getPaymentMethod($amount, $this->duitkuConfig);
            } else {
                $response = Pop::getPaymentMethod($amount, $this->duitkuConfig);
            }
            
            Log::info('Duitku Get Payment Method Response', ['amount' => $amount, 'response' => $response]);
            
            return json_decode($response, true);
        } catch (\Exception $e) {
            Log::error('Duitku Get Payment Method Error', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verify callback (works for both POP and API)
     */
    public function verifyCallback($data, $useApi = false)
    {
        try {
            if ($useApi) {
                $callback = Api::callback($this->duitkuConfig);
            } else {
                $callback = Pop::callback($this->duitkuConfig);
            }
            
            $notif = json_decode($callback, true);
            
            Log::info('Duitku Callback Verified', ['data' => $data, 'notif' => $notif]);
            
            return $notif;
        } catch (\Exception $e) {
            Log::error('Duitku Callback Verification Error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Legacy method for backward compatibility
     */
    public function createTransaction($referenceId, $amount, $paymentMethod, $productDetails, $customerName, $customerEmail, $customerPhone)
    {
        return $this->createApiTransaction($referenceId, $amount, $paymentMethod, $productDetails, $customerName, $customerEmail, $customerPhone);
    }
}
