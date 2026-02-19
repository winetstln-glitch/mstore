<?php

namespace App\Services;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MixRadiusService
{
    protected string $baseUrl;
    protected ?string $token;
    protected ?string $billingEndpoint;
    protected ?string $invoiceHtmlUrl;

    public function __construct()
    {
        $base = Setting::getValue('mixradius_base_url', env('MIXRADIUS_BASE_URL', ''));
        $this->baseUrl = rtrim((string) $base, '/');
        $this->token = Setting::getValue('mixradius_api_token', env('MIXRADIUS_API_TOKEN'));
        $this->billingEndpoint = Setting::getValue('mixradius_billing_endpoint', env('MIXRADIUS_BILLING_ENDPOINT', '/api/invoices'));
        $this->invoiceHtmlUrl = Setting::getValue('mixradius_invoice_html_url', env('MIXRADIUS_INVOICE_HTML_URL'));
    }

    // Alur: Setelah pembayaran sukses, sistem memanggil endpoint MixRADIUS
    // untuk memperbarui masa aktif layanan di FreeRADIUS/MikroTik NAS.
    public function renewUser(User $user, ?string $note = null): void
    {
        if (empty($this->baseUrl) || empty($this->token)) {
            return;
        }
        $payload = [
            'action' => 'renew',
            // Gunakan email sebagai identitas; sesuaikan jika Anda menyimpan PPPoE/Hotspot username di kolom lain
            'username' => $user->email,
            'note' => $note,
        ];
        Http::withToken($this->token)
            ->acceptJson()
            ->post($this->baseUrl . '/api/users/renew', $payload)
            ->throw();
    }

    public function changePassword(User $user, string $newPassword): bool
    {
        $endpoint = 'http://mixradius.local/api/user/update';
        $payload = ['username' => $user->email, 'password' => $newPassword];
        try {
            $response = Http::timeout(8)->acceptJson()->post($endpoint, $payload);
            if ($response->successful()) {
                return true;
            }
            Log::warning('MixRADIUS changePassword non-2xx', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('MixRADIUS changePassword error', ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function verifyCredentials(string $username, string $password): array
    {
        $endpoint = rtrim((string) $this->baseUrl, '/') . '/api/users/auth';
        try {
            $resp = Http::timeout(8)->acceptJson()->withToken((string)$this->token)->post($endpoint, [
                'username' => $username,
                'password' => $password,
                'api_key' => $this->token,
            ]);
            if ($resp->successful()) {
                $json = $resp->json();
                $ok =
                    ($json['ok'] ?? null) === true ||
                    ($json['success'] ?? null) === true ||
                    ($json['authenticated'] ?? null) === true ||
                    (isset($json['status']) && (string)$json['status'] === 'success') ||
                    (isset($json['valid']) && $json['valid'] === true);
                if ($ok) {
                    return ['ok' => true, 'data' => $json];
                }
                Log::info('MixRADIUS verifyCredentials body indicates failure', ['body' => $json]);
                return ['ok' => false, 'error' => 'invalid'];
            }
            // Fallback attempt: some deployments expect token only in body without Authorization header
            if (in_array($resp->status(), [401, 403])) {
                $resp2 = Http::timeout(8)->acceptJson()->post($endpoint, [
                    'username' => $username,
                    'password' => $password,
                    'api_key' => $this->token,
                ]);
                if ($resp2->successful()) {
                    $json = $resp2->json();
                    $ok =
                        ($json['ok'] ?? null) === true ||
                        ($json['success'] ?? null) === true ||
                        ($json['authenticated'] ?? null) === true ||
                        (isset($json['status']) && (string)$json['status'] === 'success') ||
                        (isset($json['valid']) && $json['valid'] === true);
                    if ($ok) {
                        return ['ok' => true, 'data' => $json];
                    }
                }
            }
            Log::warning('MixRADIUS verifyCredentials non-2xx', ['status' => $resp->status(), 'body' => $resp->body()]);
            return ['ok' => false, 'error' => 'invalid'];
        } catch (\Throwable $e) {
            Log::error('MixRADIUS verifyCredentials error', ['message' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'unreachable'];
        }
    }

    public function isAvailable(): bool
    {
        if (empty($this->baseUrl)) {
            return false;
        }
        try {
            $url = $this->baseUrl . '/api/ping';
            $resp = Http::timeout(5)->get($url);
            if ($resp->successful()) {
                $j = @json_decode((string)$resp->body(), true);
                return is_array($j) ? (($j['ok'] ?? $j['success'] ?? true) ? true : false) : true;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return false;
    }

    public function health(): array
    {
        $checkedAt = now()->toIso8601String();
        $serviceOk = false;
        $authOk = null;
        $serviceLatency = null;
        $authLatency = null;

        // Service reachability (/api/ping)
        try {
            $t0 = microtime(true);
            $serviceOk = $this->isAvailable();
            $serviceLatency = (int) round((microtime(true) - $t0) * 1000);
        } catch (\Throwable $e) {
            $serviceOk = false;
        }

        // Token-auth check on a protected endpoint (billing list) if token configured
        if (!empty($this->baseUrl) && !empty($this->token)) {
            try {
                $t1 = microtime(true);
                $url = $this->baseUrl . $this->billingEndpoint;
                $resp = Http::timeout(6)->acceptJson()->withToken((string)$this->token)->get($url, ['username' => '__health__']);
                $authLatency = (int) round((microtime(true) - $t1) * 1000);
                if ($resp->status() === 200) {
                    $authOk = true;
                } elseif (in_array($resp->status(), [400, 404])) {
                    // Consider 400/404 as authenticated but resource not found
                    $authOk = true;
                } elseif (in_array($resp->status(), [401, 403])) {
                    $authOk = false;
                } else {
                    $authOk = false;
                }
            } catch (\Throwable $e) {
                $authOk = false;
            }
        }

        $overallOk = $serviceOk && ($authOk !== null ? $authOk : true);

        return [
            'ok' => $overallOk,
            'ok_service' => $serviceOk,
            'ok_auth' => $authOk,
            'latency_ms' => $serviceOk ? $serviceLatency : null,
            'auth_latency_ms' => $authOk ? $authLatency : null,
            'checked_at' => $checkedAt,
        ];
    }

    public function changeCredentials(User $user, string $newUsername, string $newPassword): bool
    {
        $endpoint = rtrim((string) env('MIXRADIUS_BASE_URL', ''), '/') . '/api/users/update-credentials';
        try {
            $resp = Http::timeout(8)->acceptJson()->withToken((string)$this->token)->post($endpoint, [
                'old_username' => $user->username ?: $user->email,
                'new_username' => $newUsername,
                'new_password' => $newPassword,
            ]);
            if ($resp->successful()) {
                return true;
            }
            Log::warning('MixRADIUS changeCredentials non-2xx', ['status' => $resp->status(), 'body' => $resp->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('MixRADIUS changeCredentials error', ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function fetchInvoices(string $identity): array
    {
        if (empty($this->baseUrl) || empty($this->token)) {
            return [];
        }
        try {
            $url = $this->baseUrl . $this->billingEndpoint;
            $resp = Http::timeout(12)->acceptJson()->withToken((string)$this->token)->get($url, ['username' => $identity]);
            if ($resp->successful()) {
                $data = $resp->json();
                if (is_array($data)) {
                    return $data;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('MixRADIUS fetchInvoices json failed', ['message' => $e->getMessage()]);
        }
        if ($this->invoiceHtmlUrl) {
            try {
                $url = str_replace('{username}', urlencode($identity), $this->invoiceHtmlUrl);
                $resp = Http::timeout(12)->get($url);
                if ($resp->successful()) {
                    return $this->parseInvoiceHtml((string)$resp->body());
                }
            } catch (\Throwable $e) {
                Log::warning('MixRADIUS fetchInvoices html failed', ['message' => $e->getMessage()]);
            }
        }
        return [];
    }

    protected function parseInvoiceHtml(string $html): array
    {
        $items = [];
        $code = null;
        if (preg_match('/INV[-\\s]*-?\\d+/', $html, $m)) {
            $code = trim($m[0]);
        }
        $status = null;
        if (stripos($html, 'BELUM BAYAR') !== false) {
            $status = 'pending';
        } elseif (stripos($html, 'LUNAS') !== false || stripos($html, 'PAID') !== false) {
            $status = 'paid';
        }
        $amount = null;
        if (preg_match('/Total\\s*:\\s*Rp\\.?\\s*([\\d\\.\\,]+)/i', $html, $m2)) {
            $num = preg_replace('/[^0-9]/', '', $m2[1]);
            if ($num !== '') $amount = (int)$num;
        } elseif (preg_match('/Rp\\.?\\s*([\\d\\.\\,]+)/', $html, $m3)) {
            $num = preg_replace('/[^0-9]/', '', $m3[1]);
            if ($num !== '') $amount = (int)$num;
        }
        $due = null;
        if (preg_match('/Deadline\\s*<\\/[^>]*>\\s*([^<]+)/i', $html, $m4)) {
            $due = trim($m4[1]);
        } elseif (preg_match('/February|January|March|April|May|June|July|August|September|October|November|December\\s+\\d{1,2},\\s+\\d{4}/', $html, $m5)) {
            $due = $m5[0];
        }
        $package = null;
        if (preg_match('/(\\d+\\s*MBPS\\w*)/i', $html, $mp)) {
            $package = trim($mp[1]);
        }
        $period = null;
        if (preg_match('/(January|February|March|April|May|June|July|August|September|October|November|December)\\s+\\d{4}/', $html, $mpr)) {
            $period = trim($mpr[0]);
        }
        $items[] = [
            'code' => $code,
            'amount' => $amount,
            'due_date' => $due,
            'status' => $status,
            'package' => $package,
            'period' => $period,
        ];
        return array_filter($items, function ($i) {
            return $i['amount'] !== null || $i['code'] !== null;
        });
    }
}
