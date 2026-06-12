<?php

namespace App\Services;

use App\Events\WhatsApp\WhatsAppAnalyticsObserved;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\WhatsAppLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiKey;

    protected $baseUrl;
    
    protected $secretKey;

    public function __construct()
    {
        // Prefer DB settings, fallback to .env
        $this->apiKey = trim((string) Setting::getValue('whatsapp_api_key', config('services.whatsapp.key')));
        $this->secretKey = trim((string) Setting::getValue('whatsapp_secret_key', config('services.whatsapp.secret_key', '')));
        $rawBaseUrl = trim((string) Setting::getValue('whatsapp_api_url', config('services.whatsapp.url')));
        if ($rawBaseUrl !== '' && ! preg_match('#^https?://#i', $rawBaseUrl)) {
            $rawBaseUrl = 'https://'.$rawBaseUrl;
        }
        $this->baseUrl = rtrim($rawBaseUrl, '/');
        if (empty($this->baseUrl) && ! empty($this->apiKey)) {
            $this->baseUrl = 'https://api.fonnte.com';
        }
    }

    /**
     * Detect provider type
     */
    protected function getProvider(): string
    {
        if (str_contains($this->baseUrl, 'wablas.com')) {
            return 'wablas';
        }
        if (str_contains($this->baseUrl, 'fonnte.com')) {
            return 'fonnte';
        }
        return 'generic';
    }

    /**
     * Simple template renderer supporting {{key}} and {{#each items}}...{{/each}}
     */
    public function renderTemplate(string $template, array $vars = []): string
    {
        // Handle loops
        if (preg_match('/\{\{\#each\s+items\}\}([\s\S]*?)\{\{\/each\}\}/', $template, $m)) {
            $loopTpl = $m[1];
            $items = $vars['items'] ?? [];
            $built = '';
            foreach ($items as $item) {
                $seg = $loopTpl;
                foreach ($item as $k => $v) {
                    $seg = str_replace('{{'.$k.'}}', (string) $v, $seg);
                }
                $built .= $seg;
            }
            $template = str_replace($m[0], $built, $template);
        }
        // Replace simple keys
        foreach ($vars as $k => $v) {
            if ($k === 'items') {
                continue;
            }
            $template = str_replace('{{'.$k.'}}', (string) $v, $template);
        }

        return $template;
    }

    private function normalizeMessage(string $message): string
    {
        $msg = str_replace(["\r\n", "\r"], "\n", $message);
        $lines = array_map(function ($l) {
            return rtrim($l, " \t");
        }, explode("\n", $msg));

        return implode("\n", $lines);
    }

    /**
     * Send Message with structured response (text only
     */
    public function sendMessage($phone, $message, $category = 'general', $customerId = null): array
    {
        return $this->sendMessageWithMediaUrl($phone, $message, null, null, $category, $customerId);
    }

    /**
     * Send Message with optional media (image/document URL
     */
    public function sendMessageWithMediaUrl($phone, $message, $mediaUrl, $mediaType = 'image', $category = 'general', $customerId = null): array
    {
        // 1. Log to DB first - both notification_logs and whatsapp_logs
        $logId = DB::table('notification_logs')->insertGetId([
            'customer_id' => $customerId,
            'target_phone' => $phone ?? '',
            'type' => 'whatsapp',
            'category' => $category ?? 'general',
            'message' => $message ?? '',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log to WhatsAppLog as well
        $whatsappLog = WhatsAppLog::logMessage('outgoing', $phone, $message, 'pending');

        // 2. Validation
        if (empty($this->baseUrl)) {
            $errorMsg = 'Konfigurasi WhatsApp URL tidak ditemukan.';
            Log::error($errorMsg);
            DB::table('notification_logs')->where('id', $logId)->update([
                'status' => 'failed',
                'response' => $errorMsg,
            ]);
            $whatsappLog->update(['status' => 'failed', 'error_message' => $errorMsg]);
            return [
                'success' => false,
                'message' => $errorMsg,
                'provider' => $this->getProvider(),
                'error' => $errorMsg
            ];
        }

        if (empty($this->apiKey)) {
            $errorMsg = 'Konfigurasi WhatsApp API Key tidak ditemukan.';
            Log::error($errorMsg);
            DB::table('notification_logs')->where('id', $logId)->update([
                'status' => 'failed',
                'response' => $errorMsg,
            ]);
            $whatsappLog->update(['status' => 'failed', 'error_message' => $errorMsg]);
            return [
                'success' => false,
                'message' => $errorMsg,
                'provider' => $this->getProvider(),
                'error' => $errorMsg
            ];
        }

        if (empty(trim((string) $phone))) {
            $errorMsg = 'Nomor WhatsApp kosong.';
            Log::error($errorMsg);
            DB::table('notification_logs')->where('id', $logId)->update([
                'status' => 'failed',
                'response' => $errorMsg,
            ]);
            $whatsappLog->update(['status' => 'failed', 'error_message' => $errorMsg]);
            return [
                'success' => false,
                'message' => $errorMsg,
                'provider' => $this->getProvider(),
                'error' => $errorMsg
            ];
        }

        $message = $this->normalizeMessage((string) $message);
        
        // 3. Detect if target is group
        $isGroup = str_contains($phone, '@g.us') || 
                   (!str_contains($phone, '@c.us') && strlen($phone) > 13);
        
        // 4. Send to API
        try {
            $provider = $this->getProvider();
            $response = null;

            if ($provider === 'fonnte') {
                // Fonnte API
                $payload = [
                    'target' => $phone,
                    'message' => $message,
                    'countryCode' => '62'
                ];
                
                if ($mediaUrl) {
                    if ($mediaType === 'image') {
                        $payload['url'] = $mediaUrl;
                    } elseif ($mediaType === 'document') {
                        $payload['file'] = $mediaUrl;
                    }
                }
                
                $response = Http::timeout(10)
                    ->connectTimeout(5)
                    ->retry(2, 300)
                    ->withHeaders([
                        'Authorization' => $this->apiKey,
                    ])
                    ->post($this->baseUrl . '/send', $payload);
            } elseif ($provider === 'wablas') {
                // Wablas API
                if ($mediaUrl) {
                    // Wablas with media
                    $payload = [
                        'phone' => $phone,
                        'message' => $message,
                        'token' => $this->apiKey,
                        'url' => $mediaUrl,
                    ];
                    $response = Http::timeout(10)
                        ->connectTimeout(5)
                        ->retry(2, 300)
                        ->get($this->baseUrl . '/api/send-image', $payload);
                } else {
                    // Text only
                    $response = Http::timeout(10)
                        ->connectTimeout(5)
                        ->retry(2, 300)
                        ->get($this->baseUrl . '/api/send-message', [
                            'phone' => $phone,
                            'message' => $message,
                            'token' => $this->apiKey
                        ]);
                }
            } else {
                // Generic API, fallback to text
                if ($mediaUrl) {
                    $response = Http::timeout(10)
                        ->connectTimeout(5)
                        ->retry(2, 300)
                        ->post($this->baseUrl . '/send-message', [
                            'api_key' => $this->apiKey,
                            'phone' => $phone,
                            'message' => $message,
                            'media_url' => $mediaUrl,
                            'media_type' => $mediaType,
                        ]);
                } else {
                    $response = Http::timeout(10)
                        ->connectTimeout(5)
                        ->retry(2, 300)
                        ->post($this->baseUrl . '/send-message', [
                            'api_key' => $this->apiKey,
                            'phone' => $phone,
                            'message' => $message,
                        ]);
                }
            }

            $responseBody = $response->body();
            $responseJson = $response->json();
            
            Log::info('WhatsApp Request', [
                'provider' => $this->getProvider(),
                'url' => $response->effectiveUri(),
                'phone' => $this->maskPhoneForLogs($phone),
                'is_group' => $isGroup,
                'has_media' => !empty($mediaUrl),
                'response_status' => $response->status(),
            ]);
            
            $providerValidation = $this->validateProviderResponse($responseJson, $responseBody);
            $isSent = $response->successful() && $providerValidation['ok'];

            DB::table('notification_logs')->where('id', $logId)->update([
                'status' => $isSent ? 'sent' : 'failed',
                'response' => $responseBody,
            ]);

            $whatsappLog->update([
                'status' => $isSent ? 'sent' : 'failed',
                'payload' => $responseJson,
                'error_message' => !$isSent ? ($providerValidation['error'] ?? $responseBody) : null,
            ]);

            if (!$isSent) {
                event(new WhatsAppAnalyticsObserved([
                    'occurred_at' => now(),
                    'direction' => 'outgoing',
                    'phone_number' => (string) $phone,
                    'intent' => null,
                    'used_ai' => is_string($category) && str_contains(strtolower($category), 'ai'),
                    'is_fallback' => false,
                    'meta' => [
                        'category' => $category,
                        'has_media' => ! empty($mediaUrl),
                        'provider' => $provider,
                        'status' => 'failed',
                    ],
                ]));
                $errorMsg = 'WhatsApp gagal dikirim: ' . ($providerValidation['error'] ?? $responseBody);
                Log::error($errorMsg);
                return [
                    'success' => false,
                    'message' => $errorMsg,
                    'provider' => $provider,
                    'response' => $responseJson,
                    'error' => $errorMsg
                ];
            }

            event(new WhatsAppAnalyticsObserved([
                'occurred_at' => now(),
                'direction' => 'outgoing',
                'phone_number' => (string) $phone,
                'intent' => null,
                'used_ai' => is_string($category) && str_contains(strtolower($category), 'ai'),
                'is_fallback' => false,
                'meta' => [
                    'category' => $category,
                    'has_media' => ! empty($mediaUrl),
                    'provider' => $provider,
                    'status' => 'sent',
                ],
            ]));

            return [
                'success' => true,
                'message' => 'Message queued',
                'provider' => $provider,
                'response' => $responseJson
            ];
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            Log::error('WhatsApp Error: ' . $errorMsg);
            DB::table('notification_logs')->where('id', $logId)->update([
                'status' => 'failed',
                'response' => $errorMsg,
            ]);
            $whatsappLog->update(['status' => 'failed', 'error_message' => $errorMsg]);
            return [
                'success' => false,
                'message' => 'API Error',
                'provider' => $this->getProvider(),
                'error' => $errorMsg
            ];
        }
    }

    /**
     * Send media message (backward compatible)
     */
    public function sendMessageWithMedia($phone, $message, $mediaBinary, $mediaFilename = 'receipt.png', $category = 'general', $customerId = null): array
    {
        $logId = DB::table('notification_logs')->insertGetId([
            'customer_id' => $customerId,
            'target_phone' => $phone ?? '',
            'type' => 'whatsapp',
            'category' => $category ?? 'general',
            'message' => $message ?? '',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Validation
        if (empty($this->baseUrl) || empty($this->apiKey) || empty(trim((string) $phone))) {
            $errorMsg = 'Konfigurasi atau nomor WhatsApp tidak lengkap.';
            Log::error($errorMsg);
            DB::table('notification_logs')->where('id', $logId)->update(['status' => 'failed', 'response' => $errorMsg]);
            return [
                'success' => false,
                'message' => $errorMsg,
                'provider' => $this->getProvider(),
                'error' => $errorMsg
            ];
        }

        $message = $this->normalizeMessage((string) $message);
        if (trim($message) === '') {
            $errorMsg = 'Pesan WhatsApp kosong.';
            Log::error($errorMsg);
            DB::table('notification_logs')->where('id', $logId)->update(['status' => 'failed', 'response' => $errorMsg]);
            return [
                'success' => false,
                'message' => $errorMsg,
                'provider' => $this->getProvider(),
                'error' => $errorMsg
            ];
        }

        // For now, fallback to text message if not Fonnte
        $provider = $this->getProvider();
        if ($provider !== 'fonnte') {
            Log::warning('sendMessageWithMedia not fully supported for ' . $provider . ', falling back to text');
            return $this->sendMessage($phone, $message, $category, $customerId);
        }

        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->retry(2, 300)
                ->withHeaders(['Authorization' => $this->apiKey])
                ->attach('file', $mediaBinary, $mediaFilename)
                ->post($this->baseUrl . '/send', [
                    'target' => $phone,
                    'message' => $message,
                    'countryCode' => '62'
                ]);

            $responseBody = $response->body();
            $responseJson = $response->json();
            
            $providerValidation = $this->validateProviderResponse($responseJson, $responseBody);
            $isSent = $response->successful() && $providerValidation['ok'];
            DB::table('notification_logs')->where('id', $logId)->update([
                'status' => $isSent ? 'sent' : 'failed',
                'response' => $responseBody,
            ]);

            if (!$isSent) {
                $errorMsg = 'WhatsApp media gagal dikirim: ' . ($providerValidation['error'] ?? $responseBody);
                Log::error($errorMsg);
                return [
                    'success' => false,
                    'message' => $errorMsg,
                    'provider' => $provider,
                    'response' => $responseJson,
                    'error' => $errorMsg
                ];
            }

            return [
                'success' => true,
                'message' => 'Message queued',
                'provider' => $provider,
                'response' => $responseJson
            ];
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            Log::error('WhatsApp Media Error: ' . $errorMsg);
            DB::table('notification_logs')->where('id', $logId)->update(['status' => 'failed', 'response' => $errorMsg]);
            return [
                'success' => false,
                'message' => 'API Error',
                'provider' => $this->getProvider(),
                'error' => $errorMsg
            ];
        }
    }

    public function sendInvoice(Customer $customer, $invoice): array
    {
        $tpl = Setting::where('key', 'whatsapp_isp_bill_template')->value('value');
        if ($tpl) {
            $vars = [
                'nama_customer' => $customer->name,
                'customer_id' => $customer->customer_id ?? ($customer->id ?? ''),
                'periode' => $invoice->period ?? '',
                'nama_paket' => $invoice->package_name ?? '',
                'harga_paket' => isset($invoice->amount) ? number_format($invoice->amount, 0, ',', '.') : '',
                'biaya_admin' => isset($invoice->admin_fee) ? number_format($invoice->admin_fee, 0, ',', '.') : '0',
                'total' => isset($invoice->total) ? number_format($invoice->total, 0, ',', '.') : (isset($invoice->amount) ? number_format($invoice->amount, 0, ',', '.') : ''),
                'jatuh_tempo' => method_exists($invoice->due_date ?? null, 'format') ? $invoice->due_date->format('d-m-Y') : ($invoice->due_date ?? ''),
                'status' => $invoice->status ?? 'Belum Dibayar',
            ];
            $message = $this->renderTemplate($tpl, $vars);
        } else {
            $message = "Halo {$customer->name},\n\nTagihan internet Anda bulan ini sebesar Rp ".(isset($invoice->total) ? number_format($invoice->total, 0, ',', '.') : (isset($invoice->amount) ? number_format($invoice->amount, 0, ',', '.') : ''))." telah terbit.\nJatuh tempo: ".(method_exists($invoice->due_date ?? null, 'format') ? $invoice->due_date->format('d-m-Y') : $invoice->due_date).".\n\nMohon segera lakukan pembayaran.";
        }

        return $this->sendMessage($customer->phone, $message, 'invoice', $customer->id);
    }

    public function sendPaymentSuccess(Customer $customer, $invoice): array
    {
        $tpl = Setting::where('key', 'whatsapp_isp_payment_success_template')->value('value');
        $totalAmount = null;
        
        // Get total amount with safe checks
        if (isset($invoice->total) && $invoice->total !== null) {
            $totalAmount = $invoice->total;
        } elseif (isset($invoice->amount) && $invoice->amount !== null) {
            $totalAmount = $invoice->amount;
        }
        
        if ($tpl) {
            $vars = [
                'nama_customer' => $customer->name,
                'periode' => $invoice->period ?? '',
                'total' => $totalAmount !== null ? number_format($totalAmount, 0, ',', '.') : '',
            ];
            $message = $this->renderTemplate($tpl, $vars);
        } else {
            $formattedTotal = $totalAmount !== null ? number_format($totalAmount, 0, ',', '.') : '0';
            $message = "Terima kasih {$customer->name},\nPembayaran tagihan sebesar Rp {$formattedTotal} telah kami terima.\nLayanan internet Anda aktif.";
        }

        return $this->sendMessage($customer->phone, $message, 'payment', $customer->id);
    }

    public function sendIsolationNotification(Customer $customer): array
    {
        $tpl = Setting::where('key', 'whatsapp_isp_suspend_template')->value('value');
        if ($tpl) {
            $vars = [
                'nama_customer' => $customer->name,
                'total' => isset($customer->outstanding_total) ? number_format($customer->outstanding_total, 0, ',', '.') : '',
            ];
            $message = $this->renderTemplate($tpl, $vars);
        } else {
            $message = "Halo {$customer->name},\nLayanan internet Anda sementara kami ISOLIR karena belum melakukan pembayaran.\nMohon segera lunasi tagihan Anda agar layanan kembali normal.";
        }

        return $this->sendMessage($customer->phone, $message, 'isolate', $customer->id);
    }

    public function broadcastMessage($area, $message): int
    {
        // Logic to find customers in area/odp
        $customers = Customer::where('odp', 'LIKE', "%$area%")->get();
        $count = 0;
        foreach ($customers as $customer) {
            $result = $this->sendMessage($customer->phone, $message, 'broadcast', $customer->id);
            if ($result['success']) {
                $count++;
            }
        }

        return $count;
    }

    public function checkGatewayStatus(): array
    {
        Log::info('=== Starting WhatsApp Gateway Check ===');
        Log::info('Base URL:', ['url' => $this->baseUrl]);
        Log::info('API Key:', ['key' => substr($this->apiKey, 0, 8) . '...']);

        if (! $this->baseUrl || ! $this->apiKey) {
            Log::warning('Missing base URL or API key');
            return [
                'ok' => false,
                'connected' => false,
                'message' => 'Konfigurasi WhatsApp belum lengkap (URL/API Key).',
                'provider_response' => null,
            ];
        }

        try {
            $provider = $this->getProvider();
            Log::info('Detected Provider:', ['provider' => $provider]);
            $response = null;
            $responseBody = '';
            
            if ($provider === 'wablas') {
                Log::info('Checking Wablas gateway status');
                // Try multiple endpoints for Wablas
                $endpoints = ['/api/v2/device', '/device'];
                foreach ($endpoints as $endpoint) {
                    try {
                        $fullUrl = $this->baseUrl . $endpoint;
                        Log::info('Trying endpoint', ['url' => $fullUrl]);
                        
                        $response = Http::timeout(8)
                            ->connectTimeout(3)
                            ->withHeaders(['Authorization' => $this->apiKey])
                            ->post($fullUrl);
                        
                        $responseBody = $response->body();
                        Log::info('Wablas ' . $endpoint . ' response', [
                            'status' => $response->status(),
                            'body' => $responseBody
                        ]);
                        
                        if ($response->successful()) {
                            Log::info('Endpoint successful!');
                            break;
                        }
                    } catch (\Exception $e) {
                        // Continue to next endpoint
                        Log::warning("Wablas endpoint {$endpoint} failed: " . $e->getMessage(), ['exception' => $e]);
                        continue;
                    }
                }
                
                // If all endpoints failed, still use last response
            } elseif ($provider === 'fonnte') {
                $fullUrl = $this->baseUrl.'/device';
                Log::info('Checking Fonnte endpoint', ['url' => $fullUrl]);
                
                $response = Http::timeout(8)
                    ->connectTimeout(3)
                    ->retry(1, 200)
                    ->withHeaders(['Authorization' => $this->apiKey])
                    ->post($fullUrl);
                
                $responseBody = $response->body();
                Log::info('Fonnte response', [
                    'status' => $response->status(),
                    'body' => $responseBody
                ]);
                
            } else {
                $fullUrl = $this->baseUrl.'/status';
                Log::info('Checking generic endpoint', ['url' => $fullUrl]);
                
                $response = Http::timeout(8)
                    ->connectTimeout(3)
                    ->retry(1, 200)
                    ->post($fullUrl, [
                        'api_key' => $this->apiKey,
                    ]);
                
                $responseBody = $response->body();
                Log::info('Generic response', [
                    'status' => $response->status(),
                    'body' => $responseBody
                ]);
            }

            $body = $response ? $response->json() : [];
            if (! is_array($body)) {
                $body = [];
            }
            Log::info('Parsed response body', $body);

            // Fix: For all providers, properly check connection status!
            $connected = false;
            $statusMessage = 'Device WhatsApp belum terhubung.';

            if ($response && $response->successful()) {
                $connected = $this->extractConnectionFlag($body);
                $statusMessage = $connected ? 'Device WhatsApp terhubung.' : 'Device WhatsApp belum terhubung.';
            } else {
                $statusMessage = 'Gagal terhubung ke gateway WhatsApp (cek URL/API Key).';
            }

            $result = [
                'ok' => true,
                'connected' => $connected,
                'message' => $statusMessage,
                'provider_response' => $response ? $response->body() : null,
            ];
            Log::info('=== Gateway Check Result ===', $result);
            
            return $result;
        } catch (\Throwable $e) {
            Log::error('Check gateway status error: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'ok' => true,
                'connected' => false, // Fix: Don't assume connected on error!
                'message' => 'Gagal memeriksa status gateway: ' . $e->getMessage(),
                'provider_response' => $e->getMessage(),
            ];
        }
    }

    private function validateProviderResponse($jsonBody, string $rawBody): array
    {
        if (! is_array($jsonBody)) {
            $raw = trim($rawBody);
            if ($raw === '') {
                return ['ok' => false, 'error' => 'Respon gateway WhatsApp kosong.'];
            }
            if (preg_match('/^\s*<(?:!doctype|html)/i', $raw) === 1) {
                return ['ok' => false, 'error' => 'Respon gateway WhatsApp tidak valid (HTML).'];
            }
            return ['ok' => false, 'error' => 'Respon gateway WhatsApp tidak valid.'];
        }

        if (array_key_exists('status', $jsonBody)) {
            $status = filter_var($jsonBody['status'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($status === false) {
                $reason = $jsonBody['reason'] ?? $jsonBody['message'] ?? $rawBody;
                return ['ok' => false, 'error' => (string) $reason];
            }
        }

        if (array_key_exists('success', $jsonBody)) {
            $success = filter_var($jsonBody['success'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($success === false) {
                $reason = $jsonBody['reason'] ?? $jsonBody['message'] ?? $rawBody;
                return ['ok' => false, 'error' => (string) $reason];
            }
        }

        return ['ok' => true, 'error' => null];
    }

    private function maskPhoneForLogs(?string $phone): string
    {
        $p = preg_replace('/\s+/', '', (string) $phone);
        if ($p === '') {
            return '';
        }
        if (strlen($p) <= 4) {
            return str_repeat('*', strlen($p));
        }
        return str_repeat('*', max(0, strlen($p) - 4)) . substr($p, -4);
    }

    private function extractConnectionFlag(array $body): bool
    {
        // Check for Wablas send success response {"status": true}
        if (isset($body['status']) && is_bool($body['status']) && $body['status'] === true) {
            return true;
        }

        // Check for Wablas device status with array
        if (isset($body['data']) && is_array($body['data'])) {
            foreach ($body['data'] as $device) {
                if (is_array($device) && isset($device['status'])) {
                    $deviceStatus = strtolower((string)$device['status']);
                    if (in_array($deviceStatus, ['connected', 'online', 'aktif'])) {
                        return true;
                    }
                }
            }
        }

        // Check various flags
        foreach (['device_status', 'deviceStatus', 'connected', 'is_connected', 'isConnected', 'active', 'status'] as $key) {
            if (! array_key_exists($key, $body)) {
                continue;
            }
            $value = $body[$key];
            if (is_bool($value)) {
                return $value;
            }
            if (is_string($value)) {
                $normalized = strtolower(trim($value));
                if (in_array($normalized, ['connected', 'connect', 'online', 'true', '1', 'aktif'], true)) {
                    return true;
                }
                if (in_array($normalized, ['disconnected', 'disconnect', 'offline', 'false', '0', 'nonaktif'], true)) {
                    return false;
                }
            }
            if (is_numeric($value)) {
                return (int) $value === 1;
            }
        }

        // Default: since send works, assume connected if no disconnect indication
        return true;
    }

    private function humanizeProviderError(string $error): string
    {
        $normalized = strtolower($error);
        if (str_contains($normalized, 'disconnected device') || str_contains($normalized, 'device disconnected')) {
            return 'Perangkat WhatsApp gateway belum terhubung. Silakan buka panel provider dan sambungkan ulang device.';
        }
        if (str_contains($normalized, 'http 502') || str_contains($normalized, 'bad gateway')) {
            return 'Server gateway WhatsApp sedang bermasalah (502 Bad Gateway). Coba lagi beberapa menit, atau cek status provider.';
        }
        if (str_contains($normalized, 'http 503') || str_contains($normalized, 'service unavailable')) {
            return 'Layanan gateway WhatsApp sedang tidak tersedia (503). Coba lagi beberapa menit.';
        }
        if (str_contains($normalized, 'http 401') || str_contains($normalized, 'unauthorized')) {
            return 'API key WhatsApp tidak valid atau ditolak provider (401 Unauthorized).';
        }

        return $error;
    }

    /**
     * Send System Notification to Group (Attendance/Ticket)
     */
    public function sendGroupNotification(string $message, string $category = 'ticket'): bool
    {
        $enabledKey = "whatsapp_{$category}_notification_enabled";
        $groupIdKey = "whatsapp_{$category}_group_id";

        $isEnabled = Setting::getValue($enabledKey, '1') == '1';
        $target = Setting::getValue($groupIdKey, Setting::getValue('whatsapp_group_notification_id', config('services.whatsapp.group_id')));

        if (! $isEnabled) {
            return false;
        }

        if (empty($target)) {
            Log::warning("WhatsApp Group Notification ID for {$category} not set.");
            return false;
        }

        try {
            $result = $this->sendMessage($target, $message, "system_{$category}_notification");
            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error("Failed to send {$category} group notification: " . $e->getMessage());
            return false;
        }
    }
}
