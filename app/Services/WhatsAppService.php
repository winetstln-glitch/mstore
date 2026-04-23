<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiKey;

    protected $baseUrl;

    public function __construct()
    {
        // Prefer DB settings, fallback to .env
        $this->apiKey = Setting::getValue('whatsapp_api_key', config('services.whatsapp.key'));
        $this->baseUrl = Setting::getValue('whatsapp_api_url', config('services.whatsapp.url'));
        if (empty($this->baseUrl) && ! empty($this->apiKey)) {
            $this->baseUrl = 'https://api.fonnte.com';
        }
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
     * Send Message
     */
    public function sendMessage($phone, $message, $category = 'general', $customerId = null)
    {
        // 1. Log to DB first
        $logId = DB::table('notification_logs')->insertGetId([
            'customer_id' => $customerId,
            'target_phone' => $phone,
            'type' => 'whatsapp',
            'category' => $category,
            'message' => $message,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Send to API
        if ($this->baseUrl && $this->apiKey) {
            try {
                // Format message neatly
                $message = $this->normalizeMessage((string) $message);
                if (trim($message) === '') {
                    throw new \Exception('Pesan WhatsApp kosong setelah render');
                }
                // Detect if using Fonnte (Popular Indonesian Provider)
                if (str_contains($this->baseUrl, 'fonnte.com')) {
                    $response = Http::timeout(8)->connectTimeout(3)->retry(2, 200)->withHeaders([
                        'Authorization' => $this->apiKey,
                    ])->post($this->baseUrl.'/send', [
                        'target' => $phone,
                        'message' => $message,
                        'countryCode' => '62', // Optional, default to Indonesia
                    ]);
                }
                // Default / Generic API
                else {
                    $response = Http::timeout(8)->connectTimeout(3)->retry(2, 200)->post($this->baseUrl.'/send-message', [
                        'api_key' => $this->apiKey,
                        'phone' => $phone,
                        'message' => $message,
                    ]);
                }

                $providerValidation = $this->validateProviderResponse($response->json(), $response->body());
                $isSent = $response->successful() && $providerValidation['ok'];
                DB::table('notification_logs')->where('id', $logId)->update([
                    'status' => $isSent ? 'sent' : 'failed',
                    'response' => $response->body(),
                ]);

                if (! $isSent) {
                    $error = $providerValidation['error'] ?: ('HTTP '.$response->status().' '.$response->body());
                    throw new \Exception('Gateway WhatsApp menolak pesan: '.$error);
                }

                return true;
            } catch (\Exception $e) {
                Log::error('WhatsApp Error: '.$e->getMessage());
                DB::table('notification_logs')->where('id', $logId)->update([
                    'status' => 'failed',
                    'response' => $e->getMessage(),
                ]);
                throw $e; // Re-throw to let caller know
            }
        } else {
            $errorMsg = 'WhatsApp Configuration missing. Set WHATSAPP_API_URL and WHATSAPP_API_KEY in .env';
            Log::error($errorMsg);
            DB::table('notification_logs')->where('id', $logId)->update([
                'status' => 'failed',
                'response' => $errorMsg,
            ]);
            throw new \Exception($errorMsg);
        }
    }

    public function sendMessageWithMedia($phone, $message, $mediaBinary, $mediaFilename = 'receipt.png', $category = 'general', $customerId = null)
    {
        $logId = DB::table('notification_logs')->insertGetId([
            'customer_id' => $customerId,
            'target_phone' => $phone,
            'type' => 'whatsapp',
            'category' => $category,
            'message' => $message,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($this->baseUrl && $this->apiKey) {
            try {
                $message = $this->normalizeMessage((string) $message);
                if (trim($message) === '') {
                    throw new \Exception('Pesan WhatsApp kosong setelah render');
                }
                if (! str_contains($this->baseUrl, 'fonnte.com')) {
                    return $this->sendMessage($phone, $message, $category, $customerId);
                }

                $response = Http::timeout(12)->connectTimeout(5)->retry(2, 200)->withHeaders([
                    'Authorization' => $this->apiKey,
                ])->attach('file', $mediaBinary, $mediaFilename)->post($this->baseUrl.'/send', [
                    'target' => $phone,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

                $providerValidation = $this->validateProviderResponse($response->json(), $response->body());
                $isSent = $response->successful() && $providerValidation['ok'];
                DB::table('notification_logs')->where('id', $logId)->update([
                    'status' => $isSent ? 'sent' : 'failed',
                    'response' => $response->body(),
                ]);

                if (! $isSent) {
                    $error = $providerValidation['error'] ?: ('HTTP '.$response->status().' '.$response->body());
                    throw new \Exception('Gateway WhatsApp menolak pesan media: '.$error);
                }

                return true;
            } catch (\Exception $e) {
                Log::error('WhatsApp Media Error: '.$e->getMessage());
                DB::table('notification_logs')->where('id', $logId)->update([
                    'status' => 'failed',
                    'response' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        $errorMsg = 'WhatsApp Configuration missing. Set WHATSAPP_API_URL and WHATSAPP_API_KEY in .env';
        Log::error($errorMsg);
        DB::table('notification_logs')->where('id', $logId)->update([
            'status' => 'failed',
            'response' => $errorMsg,
        ]);
        throw new \Exception($errorMsg);
    }

    public function sendInvoice(Customer $customer, $invoice)
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
            $message = "Halo {$customer->name},\n\nTagihan internet Anda bulan ini sebesar Rp ".number_format($invoice->amount, 0, ',', '.')." telah terbit.\nJatuh tempo: ".(method_exists($invoice->due_date ?? null, 'format') ? $invoice->due_date->format('d-m-Y') : $invoice->due_date).".\n\nMohon segera lakukan pembayaran.";
        }

        return $this->sendMessage($customer->phone, $message, 'invoice', $customer->id);
    }

    public function sendPaymentSuccess(Customer $customer, $invoice)
    {
        $tpl = Setting::where('key', 'whatsapp_isp_payment_success_template')->value('value');
        if ($tpl) {
            $vars = [
                'nama_customer' => $customer->name,
                'periode' => $invoice->period ?? '',
                'total' => isset($invoice->amount) ? number_format($invoice->amount, 0, ',', '.') : (isset($invoice->total) ? number_format($invoice->total, 0, ',', '.') : ''),
            ];
            $message = $this->renderTemplate($tpl, $vars);
        } else {
            $message = "Terima kasih {$customer->name},\nPembayaran tagihan sebesar Rp ".number_format($invoice->amount ?? ($invoice->total ?? 0), 0, ',', '.')." telah kami terima.\nLayanan internet Anda aktif.";
        }

        return $this->sendMessage($customer->phone, $message, 'payment', $customer->id);
    }

    public function sendIsolationNotification(Customer $customer)
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

    public function broadcastMessage($area, $message)
    {
        // Logic to find customers in area/odp
        $customers = Customer::where('odp', 'LIKE', "%$area%")->get();
        $count = 0;
        foreach ($customers as $customer) {
            if ($this->sendMessage($customer->phone, $message, 'broadcast', $customer->id)) {
                $count++;
            }
        }

        return $count;
    }

    public function checkGatewayStatus(): array
    {
        if (! $this->baseUrl || ! $this->apiKey) {
            return [
                'ok' => false,
                'connected' => false,
                'message' => 'Konfigurasi WhatsApp belum lengkap (URL/API Key).',
                'provider_response' => null,
            ];
        }

        try {
            if (str_contains($this->baseUrl, 'fonnte.com')) {
                $response = Http::timeout(8)
                    ->connectTimeout(3)
                    ->retry(1, 200)
                    ->withHeaders(['Authorization' => $this->apiKey])
                    ->post($this->baseUrl.'/device');
            } else {
                $response = Http::timeout(8)
                    ->connectTimeout(3)
                    ->retry(1, 200)
                    ->post($this->baseUrl.'/status', [
                        'api_key' => $this->apiKey,
                    ]);
            }

            $body = $response->json();
            if (! is_array($body)) {
                $body = [];
            }

            $providerValidation = $this->validateProviderResponse($body, (string) $response->body());
            if (! $response->successful() || ! $providerValidation['ok']) {
                return [
                    'ok' => false,
                    'connected' => false,
                    'message' => $providerValidation['error'] ?: ('HTTP '.$response->status()),
                    'provider_response' => $response->body(),
                ];
            }

            $connected = $this->extractConnectionFlag($body);

            return [
                'ok' => true,
                'connected' => $connected,
                'message' => $connected ? 'Device WhatsApp terhubung.' : 'Device WhatsApp belum terhubung.',
                'provider_response' => $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'connected' => false,
                'message' => $e->getMessage(),
                'provider_response' => null,
            ];
        }
    }

    private function validateProviderResponse($jsonBody, string $rawBody): array
    {
        if (! is_array($jsonBody)) {
            return ['ok' => true, 'error' => null];
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

    private function extractConnectionFlag(array $body): bool
    {
        foreach (['device_status', 'deviceStatus', 'connected', 'is_connected', 'isConnected'] as $key) {
            if (! array_key_exists($key, $body)) {
                continue;
            }
            $value = $body[$key];
            if (is_bool($value)) {
                return $value;
            }
            if (is_string($value)) {
                $normalized = strtolower(trim($value));
                if (in_array($normalized, ['connected', 'online', 'true', '1'], true)) {
                    return true;
                }
                if (in_array($normalized, ['disconnected', 'offline', 'false', '0'], true)) {
                    return false;
                }
            }
            if (is_numeric($value)) {
                return (int) $value === 1;
            }
        }

        if (isset($body['reason']) && is_string($body['reason']) && str_contains(strtolower($body['reason']), 'disconnected')) {
            return false;
        }

        return true;
    }
}
