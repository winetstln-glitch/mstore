<?php

/**
 * @deprecated Use Modules\Network\Adapters\FreeRadiusAdapter via Network module services instead
 */

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MixRadiusService
{
    protected string $baseUrl;

    protected ?string $token;

    protected ?string $secret;

    protected ?string $authEndpoint;

    protected ?string $userInfoEndpoint;

    protected ?string $billingEndpoint;

    protected ?string $invoiceHtmlUrl;

    public function __construct()
    {
        $base = Setting::getValue('mixradius_base_url', (string) config('services.mixradius.base_url', ''));
        $this->baseUrl = rtrim(trim((string) $base), '/');
        $this->token = Setting::getValue('mixradius_api_token', config('services.mixradius.api_token'));
        $this->secret = Setting::getValue('mixradius_api_secret', config('services.mixradius.api_secret'));
        $this->authEndpoint = Setting::getValue('mixradius_auth_endpoint', config('services.mixradius.auth_endpoint'));
        $this->userInfoEndpoint = Setting::getValue('mixradius_user_info_endpoint', config('services.mixradius.user_info_endpoint'));
        $this->billingEndpoint = Setting::getValue('mixradius_billing_endpoint', config('services.mixradius.billing_endpoint', '/api/invoices'));
        $this->invoiceHtmlUrl = Setting::getValue('mixradius_invoice_html_url', config('services.mixradius.invoice_html_url'));
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
            ->post($this->baseUrl.'/api/users/renew', $payload)
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
        $base = rtrim((string) $this->baseUrl, '/');
        $configured = [];
        if (! empty($this->authEndpoint)) {
            $configured[] = $this->authEndpoint;
        }
        $candidates = array_map(function ($url) {
            return ['url' => $url, 'authHeader' => true, 'userField' => 'username', 'asForm' => false];
        }, $configured);
        $candidates = array_merge($candidates, [
            // Common endpoints
            ['url' => $base.'/api/users/auth', 'authHeader' => true, 'userField' => 'username', 'asForm' => false],
            ['url' => $base.'/api/users/auth', 'authHeader' => false, 'userField' => 'username', 'asForm' => false],
            ['url' => $base.'/api/users/auth', 'authHeader' => true, 'userField' => 'username', 'asForm' => true],
            ['url' => $base.'/api/users/auth', 'authHeader' => false, 'userField' => 'username', 'asForm' => true],
            // Alternate endpoints/field names
            ['url' => $base.'/api/user/auth', 'authHeader' => true, 'userField' => 'user', 'asForm' => false],
            ['url' => $base.'/api/user/auth', 'authHeader' => false, 'userField' => 'user', 'asForm' => false],
            ['url' => $base.'/api/user/auth', 'authHeader' => true, 'userField' => 'user', 'asForm' => true],
            ['url' => $base.'/api/user/auth', 'authHeader' => false, 'userField' => 'user', 'asForm' => true],
            ['url' => $base.'/api/auth', 'authHeader' => true, 'userField' => 'username', 'asForm' => false],
            ['url' => $base.'/api/auth', 'authHeader' => false, 'userField' => 'username', 'asForm' => false],
            ['url' => $base.'/api/auth', 'authHeader' => true, 'userField' => 'username', 'asForm' => true],
            ['url' => $base.'/api/auth', 'authHeader' => false, 'userField' => 'username', 'asForm' => true],
        ]);

        foreach ($candidates as $cand) {
            try {
                $req = Http::timeout(8)->acceptJson();
                if ($cand['authHeader'] && ! empty($this->token)) {
                    $req = $req->withToken((string) $this->token);
                    // Also pass key/secret headers for compatibility, without logging them
                    $req = $req->withHeaders(array_filter([
                        'X-Api-Key' => $this->token,
                        'X-Api-Secret' => $this->secret,
                    ]));
                }
                if (! empty($cand['asForm'])) {
                    $req = $req->asForm();
                }
                // Compose body fields depending on available credentials
                $fields = [
                    $cand['userField'] => $username,
                    'password' => $password,
                ];
                if (! empty($this->token)) {
                    $fields['api_key'] = $this->token;
                }
                if (! empty($this->secret)) {
                    // Add several common aliases to maximize compatibility
                    $fields['api_secret'] = $this->secret;
                    $fields['secret'] = $this->secret;
                    $fields['api_pass'] = $this->secret;
                }
                $resp = $req->post($cand['url'], $fields);
                if ($resp->successful()) {
                    $contentType = (string) ($resp->header('Content-Type') ?? '');
                    $body = (string) $resp->body();
                    $json = @json_decode($body, true);
                    $ok =
                        (is_array($json) && (
                            ($json['ok'] ?? null) === true ||
                            ($json['success'] ?? null) === true ||
                            ($json['authenticated'] ?? null) === true ||
                            (isset($json['status']) && in_array(strtolower((string) $json['status']), ['success', 'ok', 'true'], true)) ||
                            (isset($json['valid']) && $json['valid'] === true)
                        ));
                    if ($ok) {
                        return [
                            'ok' => true,
                            'data' => $json,
                            'endpoint' => $cand['url'],
                            'meta' => [
                                'as_form' => (bool) ($cand['asForm'] ?? false),
                                'auth_header' => (bool) ($cand['authHeader'] ?? false),
                                'user_field' => (string) ($cand['userField'] ?? 'username'),
                            ],
                        ];
                    }
                    // Some deployments return 200 with message field only
                    if (is_array($json) && isset($json['message']) && stripos((string) $json['message'], 'success') !== false) {
                        return [
                            'ok' => true,
                            'data' => $json,
                            'endpoint' => $cand['url'],
                            'meta' => [
                                'as_form' => (bool) ($cand['asForm'] ?? false),
                                'auth_header' => (bool) ($cand['authHeader'] ?? false),
                                'user_field' => (string) ($cand['userField'] ?? 'username'),
                            ],
                        ];
                    }
                    // HTML responses are treated as failure
                }
                Log::info('MixRADIUS verifyCredentials try', ['url' => $cand['url'], 'status' => $resp->status(), 'body' => $resp->body()]);
            } catch (\Throwable $e) {
                Log::warning('MixRADIUS verifyCredentials exception', ['url' => $cand['url'], 'error' => $e->getMessage()]);
            }
        }

        return ['ok' => false, 'error' => 'invalid'];
    }

    public function isAvailable(): bool
    {
        if (empty($this->baseUrl)) {
            return false;
        }
        try {
            $url = $this->baseUrl.'/api/ping';
            $resp = Http::timeout(5)->get($url);
            if ($resp->successful()) {
                $j = @json_decode((string) $resp->body(), true);

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
        if (! empty($this->baseUrl) && ! empty($this->token)) {
            try {
                $t1 = microtime(true);
                $url = $this->baseUrl.$this->billingEndpoint;
                $resp = Http::timeout(6)->acceptJson()->withToken((string) $this->token)->get($url, ['username' => '__health__']);
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

    public function resolveUsernameById(string $id): ?string
    {
        if (empty($this->baseUrl)) {
            return null;
        }
        $base = rtrim((string) $this->baseUrl, '/');
        $configured = [];
        if (! empty($this->userInfoEndpoint)) {
            $configured[] = $this->userInfoEndpoint;
        }
        $candidates = array_map(function ($url) {
            return ['url' => $url, 'params' => []];
        }, $configured);
        $candidates = array_merge($candidates, [
            // Common lookup endpoints and field names
            ['url' => $base.'/api/users/info', 'params' => ['id' => $id]],
            ['url' => $base.'/api/users/info', 'params' => ['customer_id' => $id]],
            ['url' => $base.'/api/users/detail', 'params' => ['id' => $id]],
            ['url' => $base.'/api/users/detail', 'params' => ['customer_id' => $id]],
            ['url' => $base.'/api/user/info', 'params' => ['id' => $id]],
            ['url' => $base.'/api/user/detail', 'params' => ['id' => $id]],
            ['url' => $base.'/api/user/get', 'params' => ['id' => $id]],
            ['url' => $base.'/api/user', 'params' => ['id' => $id]],
        ]);
        foreach ($candidates as $cand) {
            try {
                $req = Http::timeout(8)->acceptJson();
                if (! empty($this->token)) {
                    $req = $req->withToken((string) $this->token)
                        ->withHeaders(array_filter([
                            'X-Api-Key' => $this->token,
                            'X-Api-Secret' => $this->secret,
                        ]));
                }
                $params = $cand['params'] ?? [];
                if (! empty($this->token)) {
                    $params['api_key'] = $this->token;
                }
                if (! empty($this->secret)) {
                    $params['api_secret'] = $this->secret;
                    $params['secret'] = $this->secret;
                    $params['api_pass'] = $this->secret;
                }
                $url = $cand['url'];
                if (strpos($url, '{id}') !== false) {
                    $url = str_replace('{id}', urlencode($id), $url);
                }
                $resp = $req->get($url, $params);
                if ($resp->successful()) {
                    $json = $resp->json();
                    if (is_array($json)) {
                        if (isset($json['username']) && is_string($json['username']) && $json['username'] !== '') {
                            return $json['username'];
                        }
                        if (isset($json['data']) && is_array($json['data'])) {
                            $data = $json['data'];
                            if (isset($data['username']) && is_string($data['username']) && $data['username'] !== '') {
                                return $data['username'];
                            }
                        }
                    }
                }
                Log::info('MixRADIUS resolveUsernameById try', ['url' => $url, 'status' => $resp->status(), 'body' => $resp->body()]);
            } catch (\Throwable $e) {
                Log::warning('MixRADIUS resolveUsernameById exception', ['url' => $url, 'error' => $e->getMessage()]);
            }
        }

        return null;
    }

    public function resolveUsernameByIdWithMeta(string $id): ?array
    {
        if (empty($this->baseUrl)) {
            return null;
        }
        $base = rtrim((string) $this->baseUrl, '/');
        $configured = [];
        if (! empty($this->userInfoEndpoint)) {
            $configured[] = $this->userInfoEndpoint;
        }
        $candidates = array_map(function ($url) {
            return ['url' => $url, 'params' => []];
        }, $configured);
        $candidates = array_merge($candidates, [
            ['url' => $base.'/api/users/info', 'params' => ['id' => $id]],
            ['url' => $base.'/api/users/info', 'params' => ['customer_id' => $id]],
            ['url' => $base.'/api/users/detail', 'params' => ['id' => $id]],
            ['url' => $base.'/api/users/detail', 'params' => ['customer_id' => $id]],
            ['url' => $base.'/api/user/info', 'params' => ['id' => $id]],
            ['url' => $base.'/api/user/detail', 'params' => ['id' => $id]],
            ['url' => $base.'/api/user/get', 'params' => ['id' => $id]],
            ['url' => $base.'/api/user', 'params' => ['id' => $id]],
        ]);
        foreach ($candidates as $cand) {
            try {
                $req = Http::timeout(8)->acceptJson();
                if (! empty($this->token)) {
                    $req = $req->withToken((string) $this->token)
                        ->withHeaders(array_filter([
                            'X-Api-Key' => $this->token,
                            'X-Api-Secret' => $this->secret,
                        ]));
                }
                $params = $cand['params'] ?? [];
                if (! empty($this->token)) {
                    $params['api_key'] = $this->token;
                }
                if (! empty($this->secret)) {
                    $params['api_secret'] = $this->secret;
                    $params['secret'] = $this->secret;
                    $params['api_pass'] = $this->secret;
                }
                $url = $cand['url'];
                if (strpos($url, '{id}') !== false) {
                    $url = str_replace('{id}', urlencode($id), $url);
                }
                $resp = $req->get($url, $params);
                if ($resp->successful()) {
                    $json = $resp->json();
                    if (is_array($json)) {
                        $username = null;
                        if (isset($json['username']) && is_string($json['username']) && $json['username'] !== '') {
                            $username = $json['username'];
                        } elseif (isset($json['data']) && is_array($json['data']) && isset($json['data']['username']) && is_string($json['data']['username']) && $json['data']['username'] !== '') {
                            $username = $json['data']['username'];
                        }
                        if ($username) {
                            return ['username' => $username, 'endpoint' => $url];
                        }
                    }
                }
                Log::info('MixRADIUS resolveUsernameById try', ['url' => $url, 'status' => $resp->status(), 'body' => $resp->body()]);
            } catch (\Throwable $e) {
                Log::warning('MixRADIUS resolveUsernameById exception', ['url' => $url, 'error' => $e->getMessage()]);
            }
        }

        return null;
    }

    public function changeCredentials(User $user, string $newUsername, string $newPassword): bool
    {
        $endpoint = rtrim((string) env('MIXRADIUS_BASE_URL', ''), '/').'/api/users/update-credentials';
        try {
            $resp = Http::timeout(8)->acceptJson()->withToken((string) $this->token)->post($endpoint, [
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
            $url = $this->baseUrl.$this->billingEndpoint;
            $resp = Http::timeout(12)->acceptJson()->withToken((string) $this->token)->get($url, ['username' => $identity]);
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
                    return $this->parseInvoiceHtml((string) $resp->body());
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
            if ($num !== '') {
                $amount = (int) $num;
            }
        } elseif (preg_match('/Rp\\.?\\s*([\\d\\.\\,]+)/', $html, $m3)) {
            $num = preg_replace('/[^0-9]/', '', $m3[1]);
            if ($num !== '') {
                $amount = (int) $num;
            }
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
