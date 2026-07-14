<?php

namespace Modules\Network\Adapters;

use Modules\Network\Contracts\NetworkProviderInterface;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FreeRadiusAdapter implements NetworkProviderInterface
{
    protected string $baseUrl;

    protected ?string $token;

    protected ?string $secret;

    protected ?string $authEndpoint;

    protected ?string $userInfoEndpoint;

    protected ?string $billingEndpoint;

    public function __construct()
    {
        $config = config('network.providers.freeradius', []);
        $this->baseUrl = rtrim(trim((string) ($config['base_url'] ?? '')), '/');
        $this->token = $config['api_token'] ?? null;
        $this->secret = $config['api_secret'] ?? null;
        $this->authEndpoint = $config['auth_endpoint'] ?? '/api/users/auth';
        $this->userInfoEndpoint = $config['user_info_endpoint'] ?? '/api/users/info';
        $this->billingEndpoint = $config['billing_endpoint'] ?? '/api/invoices';
    }

    public function activateCustomer(Customer $customer): bool
    {
        return $this->callApi('/api/users/activate', [
            'username' => $this->getUsername($customer),
        ]);
    }

    public function suspendCustomer(Customer $customer): bool
    {
        return $this->callApi('/api/users/suspend', [
            'username' => $this->getUsername($customer),
        ]);
    }

    public function unsuspendCustomer(Customer $customer): bool
    {
        return $this->callApi('/api/users/unsuspend', [
            'username' => $this->getUsername($customer),
        ]);
    }

    public function disconnectCustomer(Customer $customer): bool
    {
        return $this->callApi('/api/users/disconnect', [
            'username' => $this->getUsername($customer),
        ]);
    }

    public function reconnectCustomer(Customer $customer): bool
    {
        return $this->callApi('/api/users/reconnect', [
            'username' => $this->getUsername($customer),
        ]);
    }

    public function changePassword(Customer $customer, string $newPassword): bool
    {
        return $this->callApi('/api/users/update-password', [
            'username' => $this->getUsername($customer),
            'password' => $newPassword,
        ]);
    }

    public function changeUsername(Customer $customer, string $newUsername): bool
    {
        return $this->callApi('/api/users/update-username', [
            'old_username' => $this->getUsername($customer),
            'new_username' => $newUsername,
        ]);
    }

    public function changeProfile(Customer $customer, string $newProfile): bool
    {
        return $this->callApi('/api/users/update-profile', [
            'username' => $this->getUsername($customer),
            'profile' => $newProfile,
        ]);
    }

    public function changeIPAddress(Customer $customer, string $newIp): bool
    {
        return $this->callApi('/api/users/update-ip', [
            'username' => $this->getUsername($customer),
            'ip_address' => $newIp,
        ]);
    }

    public function createCustomer(Customer $customer): bool
    {
        return $this->callApi('/api/users/create', [
            'username' => $this->getUsername($customer),
            'email' => optional($customer->user)->email,
            'name' => $customer->name,
        ]);
    }

    public function deleteCustomer(Customer $customer): bool
    {
        return $this->callApi('/api/users/delete', [
            'username' => $this->getUsername($customer),
        ]);
    }

    public function customerExists(Customer $customer): bool
    {
        $response = $this->getApi('/api/users/exists', [
            'username' => $this->getUsername($customer),
        ]);

        return $response['ok'] ?? false;
    }

    public function health(): array
    {
        $checkedAt = now()->toIso8601String();
        $serviceOk = false;
        $authOk = null;
        $serviceLatency = null;
        $authLatency = null;

        try {
            $t0 = microtime(true);
            $serviceOk = $this->ping();
            $serviceLatency = (int) round((microtime(true) - $t0) * 1000);
        } catch (\Throwable $e) {
            $serviceOk = false;
        }

        if (! empty($this->baseUrl) && ! empty($this->token)) {
            try {
                $t1 = microtime(true);
                $url = $this->baseUrl . $this->billingEndpoint;
                $resp = Http::timeout(6)->acceptJson()->withToken((string) $this->token)->get($url, ['username' => '__health__']);
                $authLatency = (int) round((microtime(true) - $t1) * 1000);
                if ($resp->status() === 200) {
                    $authOk = true;
                } elseif (in_array($resp->status(), [400, 404])) {
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

    public function ping(): bool
    {
        if (empty($this->baseUrl)) {
            return false;
        }
        try {
            $url = $this->baseUrl . '/api/ping';
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

    public function syncCustomer(Customer $customer): bool
    {
        return $this->callApi('/api/users/sync', [
            'username' => $this->getUsername($customer),
        ]);
    }

    public function syncAll(): int
    {
        $response = $this->callApi('/api/users/sync-all', []);
        return $response['count'] ?? 0;
    }

    public function verifyCredentials(string $username, string $password): array
    {
        if (empty($this->baseUrl)) {
            return ['ok' => false, 'error' => 'invalid'];
        }

        $configured = [];
        if (! empty($this->authEndpoint)) {
            $configured[] = $this->authEndpoint;
        }
        $candidates = array_map(function ($url) {
            return ['url' => $url, 'authHeader' => true, 'userField' => 'username', 'asForm' => false];
        }, $configured);
        $candidates = array_merge($candidates, [
            ['url' => '/api/users/auth', 'authHeader' => true, 'userField' => 'username', 'asForm' => false],
            ['url' => '/api/users/auth', 'authHeader' => false, 'userField' => 'username', 'asForm' => false],
            ['url' => '/api/users/auth', 'authHeader' => true, 'userField' => 'username', 'asForm' => true],
            ['url' => '/api/user/auth', 'authHeader' => true, 'userField' => 'user', 'asForm' => false],
            ['url' => '/api/user/auth', 'authHeader' => false, 'userField' => 'user', 'asForm' => false],
            ['url' => '/api/user/auth', 'authHeader' => true, 'userField' => 'user', 'asForm' => true],
            ['url' => '/api/user/auth', 'authHeader' => false, 'userField' => 'user', 'asForm' => true],
            ['url' => '/api/auth', 'authHeader' => true, 'userField' => 'username', 'asForm' => false],
            ['url' => '/api/auth', 'authHeader' => false, 'userField' => 'username', 'asForm' => false],
            ['url' => '/api/auth', 'authHeader' => true, 'userField' => 'username', 'asForm' => true],
            ['url' => '/api/auth', 'authHeader' => false, 'userField' => 'username', 'asForm' => true],
        ]);

        foreach ($candidates as $cand) {
            try {
                $req = Http::timeout(8)->acceptJson();
                if ($cand['authHeader'] && ! empty($this->token)) {
                    $req = $req->withToken((string) $this->token)
                        ->withHeaders(array_filter([
                            'X-Api-Key' => $this->token,
                            'X-Api-Secret' => $this->secret,
                        ]));
                }
                if (! empty($cand['asForm'])) {
                    $req = $req->asForm();
                }
                $fields = [
                    $cand['userField'] => $username,
                    'password' => $password,
                ];
                if (! empty($this->token)) {
                    $fields['api_key'] = $this->token;
                }
                if (! empty($this->secret)) {
                    $fields['api_secret'] = $this->secret;
                    $fields['secret'] = $this->secret;
                    $fields['api_pass'] = $this->secret;
                }
                $resp = $req->post($this->baseUrl . $cand['url'], $fields);
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
                }
                Log::info('FreeRadiusAdapter: verifyCredentials try', ['url' => $cand['url'], 'status' => $resp->status(), 'body' => $resp->body()]);
            } catch (\Throwable $e) {
                Log::warning('FreeRadiusAdapter: verifyCredentials exception', ['url' => $cand['url'], 'error' => $e->getMessage()]);
            }
        }

        return ['ok' => false, 'error' => 'invalid'];
    }

    public function resolveUsernameByIdWithMeta(string $id): array
    {
        if (empty($this->baseUrl)) {
            return ['username' => '', 'endpoint' => ''];
        }

        $configured = [];
        if (! empty($this->userInfoEndpoint)) {
            $configured[] = $this->userInfoEndpoint;
        }
        $candidates = array_map(function ($url) {
            return ['url' => $url, 'params' => []];
        }, $configured);
        $candidates = array_merge($candidates, [
            ['url' => '/api/users/info', 'params' => ['id' => $id]],
            ['url' => '/api/users/info', 'params' => ['customer_id' => $id]],
            ['url' => '/api/users/detail', 'params' => ['id' => $id]],
            ['url' => '/api/users/detail', 'params' => ['customer_id' => $id]],
            ['url' => '/api/user/info', 'params' => ['id' => $id]],
            ['url' => '/api/user/detail', 'params' => ['id' => $id]],
            ['url' => '/api/user/get', 'params' => ['id' => $id]],
            ['url' => '/api/user', 'params' => ['id' => $id]],
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
                $resp = $req->get($this->baseUrl . $url, $params);
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
                Log::info('FreeRadiusAdapter: resolveUsernameById try', ['url' => $url, 'status' => $resp->status(), 'body' => $resp->body()]);
            } catch (\Throwable $e) {
                Log::warning('FreeRadiusAdapter: resolveUsernameById exception', ['url' => $cand['url'], 'error' => $e->getMessage()]);
            }
        }

        return ['username' => '', 'endpoint' => ''];
    }

    protected function getUsername(Customer $customer): string
    {
        return $customer->pppoe_user ?? optional($customer->user)->username ?? optional($customer->user)->email ?? '';
    }

    protected function callApi(string $endpoint, array $data = []): bool
    {
        if (empty($this->baseUrl)) {
            Log::warning('FreeRadiusAdapter: baseUrl not configured');
            return false;
        }

        try {
            $req = Http::timeout(8)->acceptJson();
            if (! empty($this->token)) {
                $req = $req->withToken($this->token)
                    ->withHeaders(array_filter([
                        'X-Api-Key' => $this->token,
                        'X-Api-Secret' => $this->secret,
                    ]));
            }

            $fields = $data;
            if (! empty($this->token)) {
                $fields['api_key'] = $this->token;
            }
            if (! empty($this->secret)) {
                $fields['api_secret'] = $this->secret;
                $fields['secret'] = $this->secret;
            }

            $resp = $req->post($this->baseUrl . $endpoint, $fields);
            if ($resp->successful()) {
                $json = @json_decode((string) $resp->body(), true);
                if (is_array($json)) {
                    return ($json['ok'] ?? $json['success'] ?? false) === true;
                }
                return true;
            }
            Log::warning('FreeRadiusAdapter: API call failed', [
                'endpoint' => $endpoint,
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('FreeRadiusAdapter: API call error', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function getApi(string $endpoint, array $params = []): array
    {
        if (empty($this->baseUrl)) {
            Log::warning('FreeRadiusAdapter: baseUrl not configured');
            return ['ok' => false];
        }

        try {
            $req = Http::timeout(8)->acceptJson();
            if (! empty($this->token)) {
                $req = $req->withToken($this->token)
                    ->withHeaders(array_filter([
                        'X-Api-Key' => $this->token,
                        'X-Api-Secret' => $this->secret,
                    ]));
            }

            $fields = $params;
            if (! empty($this->token)) {
                $fields['api_key'] = $this->token;
            }
            if (! empty($this->secret)) {
                $fields['api_secret'] = $this->secret;
                $fields['secret'] = $this->secret;
            }

            $resp = $req->get($this->baseUrl . $endpoint, $fields);
            if ($resp->successful()) {
                $json = @json_decode((string) $resp->body(), true);
                return is_array($json) ? $json : ['ok' => true];
            }
            Log::warning('FreeRadiusAdapter: API get failed', [
                'endpoint' => $endpoint,
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
            return ['ok' => false];
        } catch (\Throwable $e) {
            Log::error('FreeRadiusAdapter: API get error', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);
            return ['ok' => false];
        }
    }
}
