<?php

namespace App\Services\Olt\Drivers;

use App\Services\Olt\OltDriverInterface;
use App\Services\Olt\TelnetClient;
use App\Services\Olt\OltHttpClient;
use App\Models\Olt;
use Illuminate\Support\Facades\Http;

class HsgqDriver implements OltDriverInterface
{
    protected $client;
    protected $httpClient;
    protected $olt;
    protected $mode = 'telnet';

    public function __construct()
    {
        $this->client = new TelnetClient();
        $this->httpClient = new OltHttpClient();
    }

    public function connect(Olt $olt, $timeout = 10)
    {
        $this->olt = $olt;
        
        if (in_array($olt->port, [80, 443, 8080, 8000, 1001])) {
            try {
                $this->httpClient->connect($olt->host, $olt->port, $timeout);
                $this->mode = 'http';
                try {
                    $this->loginHttp($olt->username, $olt->password);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('HSGQ HTTP login failed, continuing without login: ' . $e->getMessage());
                }
                return;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('HSGQ HTTP connect failed, falling back to telnet: ' . $e->getMessage());
            }
        }

        $this->mode = 'telnet';
        $this->connectTelnet($olt, $timeout);
    }

    protected function connectTelnet(Olt $olt, $timeout)
    {
        $this->client->connect($olt->host, $olt->port, $timeout);
        
        try {
            $this->client->login(
                $olt->username, 
                $olt->password, 
                ['Login:', 'User Name:', 'user:', 'User:', 'Username:', 'username:', 'login:', 'Login'], 
                ['Password:', 'Pass:', 'password:', 'pass:']
            );

            $this->client->waitPrompt(['>', '#', '$']);

            $this->client->setPrompt(['>', '#', '$']); 
            
            $this->client->write('enable');
            
            try {
                $buffer = $this->client->waitPrompt(['Password:', 'Pass:', 'password:', 'pass:', '#', '>', '$']);
                if (stripos($buffer, 'Password:') !== false || stripos($buffer, 'Pass:') !== false) {
                    $this->client->write($olt->password);
                    $this->client->waitPrompt(['#', '>', '$']);
                }
            } catch (\Exception $e) {
            }

            $this->client->setPrompt(['#', '>', '$']); 
            
            try {
                $this->client->write('terminal length 0');
                $this->client->waitPrompt(['#', '>', '$']);
            } catch (\Exception $e) {
            }
        } catch (\Exception $e) {
            throw new \Exception("Telnet Login failed: " . $e->getMessage());
        }
    }

    protected function loginHttp($username, $password)
    {
        // Try common HSGQ login paths
        $paths = [
            '/boaform/admin/formLogin',
            '/login.asp',
            '/goform/login'
        ];

        $success = false;
        $lastError = '';

        foreach ($paths as $path) {
            try {
                \Illuminate\Support\Facades\Log::info("HSGQ HTTP Login Attempt: {$path}");
                $response = $this->httpClient->post($path, [
                    'username' => $username,
                    'password' => $password,
                    'save_login' => 0
                ]);

                \Illuminate\Support\Facades\Log::info("HSGQ HTTP Login Response: {$response->status()}");
                
                if ($response->status() === 200 || $response->status() === 302) {
                    $success = true;
                    break;
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                \Illuminate\Support\Facades\Log::error("HSGQ HTTP Login Error: {$e->getMessage()}");
            }
        }

        if (!$success) {
            throw new \Exception("HTTP Login failed. Tried common paths. Last error: " . $lastError);
        }
    }

    public function getOnus()
    {
        $nmsOnus = $this->getOnusFromNms();
        if (!empty($nmsOnus)) {
            return $nmsOnus;
        }

        $externalUrl = env('HSGQ_ONU_JSON_URL');
        if ($externalUrl) {
            try {
                \Illuminate\Support\Facades\Log::info("HSGQ External JSON Fetch Attempt: {$externalUrl}");

                $headers = [];
                $headersEnv = env('HSGQ_ONU_JSON_HEADERS');
                if ($headersEnv) {
                    $decoded = json_decode($headersEnv, true);
                    if (is_array($decoded)) {
                        $headers = $decoded;
                    }
                }

                $http = Http::withOptions([
                    'timeout' => 30,
                    'verify' => false,
                ]);

                if (!empty($headers)) {
                    $http = $http->withHeaders($headers);
                }

                $response = $http->get($externalUrl);

                if ($response->successful()) {
                    $body = trim($response->body() ?? '');
                    if ($body !== '') {
                        $data = json_decode($body, true);
                        if (is_array($data)) {
                            $onus = $this->parseOnuJson($data);
                            if (count($onus) > 0) {
                                return $onus;
                            } else {
                                $keys = implode(',', array_keys($data));
                                $preview = substr($body, 0, 200);
                                \Illuminate\Support\Facades\Log::warning("HSGQ External JSON Parsed 0 ONUs. Keys: {$keys}. Preview: {$preview}");
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::warning("HSGQ External JSON Decode Failed or not array. Preview: " . substr($body, 0, 200));
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::warning("HSGQ External JSON Fetch Empty Body: {$externalUrl}");
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning("HSGQ External JSON Fetch Response: {$externalUrl} status " . $response->status() . " length " . strlen($response->body()));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("HSGQ External JSON Fetch Error: " . $e->getMessage());
            }
        }

        if ($this->mode === 'http') {
            $httpOnus = [];
            try {
                $httpOnus = $this->getOnusHttp();
                if (!empty($httpOnus)) {
                    return $httpOnus;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("HSGQ HTTP ONU fetch failed: {$e->getMessage()}");
            }

            \Illuminate\Support\Facades\Log::warning("HSGQ HTTP mode active but no ONU data found. Trying telnet fallback on port 23.");

            try {
                $telnetOnus = $this->getOnusTelnetFallback();
                if (!empty($telnetOnus)) {
                    \Illuminate\Support\Facades\Log::info("HSGQ Telnet fallback returned " . count($telnetOnus) . " ONUs.");
                    return $telnetOnus;
                }
                \Illuminate\Support\Facades\Log::warning("HSGQ Telnet fallback returned 0 ONUs.");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("HSGQ Telnet fallback failed: {$e->getMessage()}");
            }

            return [];
        }

        return $this->getOnusTelnet();
    }

    protected function getOnusFromNms(): array
    {
        $baseUrl = rtrim((string) env('NMS_API_BASE_URL', ''), '/');
        $apiKey = (string) env('NMS_API_KEY', '');

        if ($baseUrl === '' || $apiKey === '' || !$this->olt) {
            return [];
        }

        try {
            $client = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])->withOptions([
                'timeout' => 30,
                'verify' => false,
            ]);

            $oltsResponse = $client->get($baseUrl . '/olts');
            if (!$oltsResponse->successful()) {
                \Illuminate\Support\Facades\Log::warning('NMS API /olts request failed with status ' . $oltsResponse->status());
                return [];
            }

            $oltsBody = $oltsResponse->json();
            if (!is_array($oltsBody)) {
                \Illuminate\Support\Facades\Log::warning('NMS API /olts response is not valid JSON array/object');
                return [];
            }

            $data = $oltsBody['data'] ?? $oltsBody['olts'] ?? null;
            if (!is_array($data)) {
                \Illuminate\Support\Facades\Log::warning('NMS API /olts response does not contain data array');
                return [];
            }

            $nmsOltId = null;
            foreach ($data as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $ip = $row['ip_address'] ?? $row['host'] ?? null;
                if ($ip && $ip === $this->olt->host) {
                    $nmsOltId = $row['olt_id'] ?? $row['id'] ?? null;
                    break;
                }
            }

            if ($nmsOltId === null) {
                \Illuminate\Support\Facades\Log::warning('NMS API could not find matching OLT for host ' . $this->olt->host);
                return [];
            }

            $onusResponse = $client->get($baseUrl . '/olts/' . $nmsOltId . '/onus');
            if (!$onusResponse->successful()) {
                \Illuminate\Support\Facades\Log::warning('NMS API /olts/' . $nmsOltId . '/onus request failed with status ' . $onusResponse->status());
                return [];
            }

            $onusBody = $onusResponse->body();
            if ($onusBody === '' || $onusBody === null) {
                \Illuminate\Support\Facades\Log::warning('NMS API /olts/' . $nmsOltId . '/onus returned empty body');
                return [];
            }

            $decoded = json_decode($onusBody, true);
            if (!is_array($decoded)) {
                \Illuminate\Support\Facades\Log::warning('NMS API /olts/' . $nmsOltId . '/onus JSON decode failed. Preview: ' . substr($onusBody, 0, 200));
                return [];
            }

            $onus = $this->parseOnuJson($decoded);
            if (count($onus) > 0) {
                \Illuminate\Support\Facades\Log::info('NMS API returned ' . count($onus) . ' ONUs for OLT ' . $this->olt->host);
                return $onus;
            }

            \Illuminate\Support\Facades\Log::warning('NMS API parsed 0 ONUs for OLT ' . $this->olt->host);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('NMS API error: ' . $e->getMessage());
        }

        return [];
    }
    
    protected function getOnusTelnet()
    {
        $commands = [
            'show gpon onu state',
            'show gpon onu info',
            'show gpon onu optical-power',
            'show gpon onu distance',
            'show onu status',
            'show onu all',
        ];

        $customCommands = env('HSGQ_ONU_TELNET_COMMANDS');
        if ($customCommands) {
            $decoded = json_decode($customCommands, true);
            if (is_array($decoded)) {
                $commands = array_values(array_filter(array_map('trim', $decoded)));
            } else {
                $parts = array_filter(array_map('trim', explode(',', $customCommands)));
                if (!empty($parts)) {
                    $commands = $parts;
                }
            }
        }
        
        $output = '';
        foreach ($commands as $cmd) {
            try {
                \Illuminate\Support\Facades\Log::info("HSGQ Telnet Command Exec: {$cmd}");
                $result = $this->client->exec($cmd);
                \Illuminate\Support\Facades\Log::info("HSGQ Telnet Command Result Preview: " . substr($result, 0, 300));
                $trimmed = trim($result);

                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    $onusFromJson = $this->parseOnuJson($decoded);
                    if (count($onusFromJson) > 0) {
                        return $onusFromJson;
                    }
                }

                if (!str_contains(strtolower($result), 'unknown') && !str_contains(strtolower($result), 'error')) {
                    $output .= "\n" . $result;
                }
            } catch (\Exception $e) {
                // Ignore command errors
            }
        }
        
        return $this->parseOnuOutput($output);
    }

    protected function getOnusTelnetFallback()
    {
        $tempOlt = new Olt([
            'host' => $this->olt->host,
            'port' => 23,
            'username' => $this->olt->username,
            'password' => $this->olt->password,
        ]);

        $this->connectTelnet($tempOlt, 10);

        return $this->getOnusTelnet();
    }
    
    
    protected function getOnusHttp()
    {
        $aggregatedOnus = [];

        for ($portId = 1; $portId <= 16; $portId++) {
            $path = '/gponont_mgmt?form=res_name&port_id=' . $portId;
            try {
                \Illuminate\Support\Facades\Log::info("HSGQ HTTP GPONONT Fetch Attempt: {$path}");
                $response = $this->httpClient->get($path);

                \Illuminate\Support\Facades\Log::info("HSGQ HTTP GPONONT Fetch Response: {$response->status()} - Length: " . strlen($response->body()));

                if (!$response->successful()) {
                    continue;
                }

                $body = $response->body();
                if ($body === '' || $body === null || strlen($body) < 50) {
                    \Illuminate\Support\Facades\Log::warning("HSGQ HTTP GPONONT Empty or Too Short Body for {$path}. Preview: " . substr((string) $body, 0, 200));
                    continue;
                }

                \Illuminate\Support\Facades\Log::info("HSGQ HTTP GPONONT Body Preview {$path}: " . substr($body, 0, 500));

                $body = ltrim($body);
                if ($body !== '' && $body[0] !== '{' && $body[0] !== '[') {
                    $posArray = strpos($body, '[');
                    $posObject = strpos($body, '{');
                    $positions = [];
                    if ($posArray !== false) {
                        $positions[] = $posArray;
                    }
                    if ($posObject !== false) {
                        $positions[] = $posObject;
                    }
                    if (!empty($positions)) {
                        $firstPos = min($positions);
                        $body = substr($body, $firstPos);
                    }
                }

                $decoded = json_decode($body, true);
                $onus = [];

                if (is_array($decoded)) {
                    $onus = $this->parseOnuJson($decoded);
                    if (empty($onus)) {
                        \Illuminate\Support\Facades\Log::warning("HSGQ HTTP GPONONT JSON Parsed 0 ONUs for {$path}. Keys: " . implode(',', array_keys($decoded)));
                    }
                } else {
                    $onus = $this->parseOnuHtml($body);
                    if (empty($onus)) {
                        \Illuminate\Support\Facades\Log::warning("HSGQ HTTP GPONONT HTML Parsed 0 ONUs for {$path}.");
                    }
                }

                if (!empty($onus)) {
                    $aggregatedOnus = array_merge($aggregatedOnus, $onus);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("HSGQ HTTP GPONONT Fetch Error: {$e->getMessage()}");
                continue;
            }
        }

        if (!empty($aggregatedOnus)) {
            return $aggregatedOnus;
        }

        $paths = [
            '/onu_allow_list.asp',
            '/onu_list.asp',
            '/epon/onu_list.asp',
            '/gpon/onu_list.asp',
            '/onu_index.asp',
            '/device/onu_list.asp',
            '/admin/onu_info.asp',
            '/lineprofile?form=table',
            '/switch_port?form=portlist_info'
        ];
        
        foreach ($paths as $path) {
            try {
                \Illuminate\Support\Facades\Log::info("HSGQ HTTP Fetch Attempt: {$path}");
                $response = $this->httpClient->get($path);
                
                \Illuminate\Support\Facades\Log::info("HSGQ HTTP Fetch Response: {$response->status()} - Length: " . strlen($response->body()));

                if ($response->successful() && strlen($response->body()) > 100) {
                    $onus = $this->parseOnuHtml($response->body());
                    if (count($onus) > 0) {
                        return $onus;
                    } else {
                         \Illuminate\Support\Facades\Log::warning("HSGQ HTTP Parsed 0 ONUs from {$path}. Content preview: " . substr($response->body(), 0, 500));
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("HSGQ HTTP Fetch Error: {$e->getMessage()}");
                continue;
            }
        }

        $jsonPaths = [
            '/api/onu/list',
            '/api/onu',
            '/api/ont/list',
            '/api/ont',
            '/gponmgmt?form=gpon_setting',
            '/gponmgmt?form=gpon_ont',
            '/gponmgmt?form=ont_list',
            '/gponmgmt?form=ont_info',
            '/gponmgmt?form=ont',
            '/gponmgmt?form=onu_list',
            '/api/pon/onu',
            '/api/pon/onu/list',
            '/api/gpon/onu',
            '/api/gpon/onu/list',
            '/pon/onu',
            '/pon/onu/list',
            '/gpon/onu',
            '/gpon/onu/list',
            '/onu/list'
        ];

        $envPath = env('HSGQ_ONU_JSON_PATH');
        if ($envPath) {
            array_unshift($jsonPaths, $envPath);
        }

        foreach ($jsonPaths as $path) {
            try {
                \Illuminate\Support\Facades\Log::info("HSGQ HTTP JSON Fetch Attempt: {$path}");
                $response = $this->httpClient->get($path);

                if (!$response->successful()) {
                    \Illuminate\Support\Facades\Log::warning("HSGQ HTTP JSON Fetch Response: {$path} status " . $response->status() . " length " . strlen($response->body()));
                    continue;
                }

                $body = $response->body();
                if ($body === '' || $body === null) {
                    \Illuminate\Support\Facades\Log::warning("HSGQ HTTP JSON Fetch Empty Body: {$path}");
                    continue;
                }

                $body = ltrim($body);
                if ($body !== '' && $body[0] !== '{' && $body[0] !== '[') {
                    $posArray = strpos($body, '[');
                    $posObject = strpos($body, '{');
                    $positions = [];
                    if ($posArray !== false) {
                        $positions[] = $posArray;
                    }
                    if ($posObject !== false) {
                        $positions[] = $posObject;
                    }
                    if (!empty($positions)) {
                        $firstPos = min($positions);
                        $body = substr($body, $firstPos);
                    }
                }

                $data = json_decode($body, true);
                if (!is_array($data)) {
                    \Illuminate\Support\Facades\Log::warning("HSGQ HTTP JSON Decode Failed or not array for path {$path}. Preview: " . substr($body, 0, 200));
                    continue;
                }

                $onus = $this->parseOnuJson($data);
                if (count($onus) > 0) {
                    return $onus;
                } else {
                    \Illuminate\Support\Facades\Log::warning("HSGQ HTTP JSON Parsed 0 ONUs from {$path}. Keys: " . implode(',', array_keys($data)));
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("HSGQ HTTP JSON Fetch Error: {$e->getMessage()}");
                continue;
            }
        }

        throw new \Exception("HTTP Fetch failed. Could not find valid ONU list page. Check logs for details.");
    }
    
    protected function parseOnuHtml($html)
    {
        $onus = [];
        preg_match_all('/([0-9a-fA-F]{2}[:.-]?){5}[0-9a-fA-F]{2}/', $html, $matches);
        
        if (!empty($matches[0])) {
            foreach (array_unique($matches[0]) as $index => $mac) {
                $mac = str_replace(['-', '.'], ':', $mac);
                $onus[] = [
                    'interface' => 'WEB-' . ($index + 1), 
                    'status' => 'online', // Assume online if present in the list
                    'mac_address' => $mac,
                    'distance' => 0,
                    'signal' => null,
                    'serial_number' => null,
                    'name' => null
                ];
            }
        }

        if (empty($onus)) {
            preg_match_all('/\b[0-9A-Z]{16}\b/', $html, $snMatches);
            if (!empty($snMatches[0])) {
                foreach (array_unique($snMatches[0]) as $index => $sn) {
                    $onus[] = [
                        'interface' => 'WEB-SN-' . ($index + 1),
                        'status' => 'online',
                        'mac_address' => null,
                        'distance' => 0,
                        'signal' => null,
                        'serial_number' => $sn,
                        'name' => null
                    ];
                }
            }
        }
        
        return $onus;
    }

    protected function parseOnuJson($data)
    {
        $onus = [];
        $this->collectOnuFromJson($data, $onus);
        return $onus;
    }

    protected function collectOnuFromJson($node, array &$onus)
    {
        if (is_array($node)) {
            $isList = array_keys($node) === range(0, count($node) - 1);

            if ($isList) {
                foreach ($node as $item) {
                    if (is_array($item)) {
                        $this->addOnuFromJsonRow($item, $onus);
                        $this->collectOnuFromJson($item, $onus);
                    }
                }
            } else {
                foreach ($node as $value) {
                    $this->collectOnuFromJson($value, $onus);
                }
            }
        }
    }

    protected function addOnuFromJsonRow(array $row, array &$onus)
    {
        $lower = array_change_key_case($row, CASE_LOWER);

        $macKeys = ['mac', 'mac_address', 'macaddr', 'macaddr1', 'macaddr2', 'macaddr3', 'onumac', 'onu_mac'];
        $snKeys = ['sn', 'serial', 'serial_number', 'sn_value', 'onusn', 'onu_sn', 'sn_mac'];
        $ifaceKeys = ['interface', 'pon', 'pon_port', 'port', 'ponid', 'location', 'slotport', 'board', 'portid', 'ont_id_text'];
        $statusKeys = ['status', 'state', 'online', 'onu_state', 'linkstate'];
        $nameKeys = ['name', 'nama', 'customer', 'user'];
        $signalKeys = ['rx_power', 'rxpower', 'rx', 'power', 'signal'];

        $mac = null;
        foreach ($macKeys as $key) {
            if (isset($lower[$key]) && $lower[$key] !== '') {
                $mac = (string) $lower[$key];
                break;
            }
        }

        $serial = null;
        foreach ($snKeys as $key) {
            if (isset($lower[$key]) && $lower[$key] !== '') {
                $serial = (string) $lower[$key];
                break;
            }
        }

        if ($mac === null && $serial === null) {
            return;
        }

        $interface = null;
        foreach ($ifaceKeys as $key) {
            if (isset($lower[$key]) && $lower[$key] !== '') {
                $interface = (string) $lower[$key];
                break;
            }
        }

        if ($interface === null) {
            $interface = 'API-' . (count($onus) + 1);
        }

        $statusValue = null;
        foreach ($statusKeys as $key) {
            if (isset($lower[$key])) {
                $statusValue = $lower[$key];
                break;
            }
        }

        $dbStatus = 'online';
        if (is_string($statusValue)) {
            $v = strtolower($statusValue);
            if (in_array($v, ['offline', 'down', 'lost'])) {
                $dbStatus = 'offline';
            } elseif (str_contains($v, 'los')) {
                $dbStatus = 'los';
            } else {
                $dbStatus = 'online';
            }
        } elseif (is_bool($statusValue)) {
            $dbStatus = $statusValue ? 'online' : 'offline';
        }

        $name = null;
        foreach ($nameKeys as $key) {
            if (isset($lower[$key]) && $lower[$key] !== '') {
                $name = (string) $lower[$key];
                break;
            }
        }

        $signal = null;
        foreach ($signalKeys as $key) {
            if (isset($lower[$key]) && $lower[$key] !== '') {
                $value = $lower[$key];
                $signal = is_numeric($value) ? (float) $value : $value;
                break;
            }
        }

        $onus[] = [
            'interface' => $interface,
            'status' => $dbStatus,
            'mac_address' => $mac,
            'distance' => 0,
            'signal' => $signal,
            'serial_number' => $serial,
            'name' => $name
        ];
    }

    protected function parseOnuOutput($output)
    {
        $onus = [];
        $lines = explode("\n", $output);
        $index = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, 'Interface') || str_starts_with($line, 'OnuIndex')) {
                continue;
            }

            if (preg_match('/^((?:EPON|GPON|XPON)?\d+\/\d+[:\/]\d+)\s+(\S+)\s+([0-9a-fA-F:.]+)(?:\s+(\d+))?/', $line, $matches)) {
                $status = strtolower($matches[2]);
                $dbStatus = 'offline';
                
                if (in_array($status, ['register', 'online', 'auth', 'up'])) {
                    $dbStatus = 'online';
                } elseif (in_array($status, ['deregister', 'offline', 'lost', 'down'])) {
                    $dbStatus = 'offline'; 
                } elseif (str_contains($status, 'los')) {
                    $dbStatus = 'los';
                }

                $onus[] = [
                    'interface' => $matches[1],
                    'status' => $dbStatus,
                    'mac_address' => $matches[3],
                    'distance' => isset($matches[4]) ? (int)$matches[4] : 0,
                    'signal' => null, // Requires 'show onu optical-info' or similar
                    'serial_number' => null, // Often separate command
                    'name' => null
                ];
                $index++;
                continue;
            }

            if (preg_match('/([0-9a-fA-F]{2}[:.-]?){5}[0-9a-fA-F]{2}/', $line, $macMatch)) {
                $mac = str_replace(['-', '.'], ':', $macMatch[0]);
                $onus[] = [
                    'interface' => 'CLI-' . (++$index),
                    'status' => 'online',
                    'mac_address' => $mac,
                    'distance' => 0,
                    'signal' => null,
                    'serial_number' => null,
                    'name' => null
                ];
                continue;
            }

            if (preg_match('/\b[0-9A-Z]{16}\b/', $line, $snMatch)) {
                $onus[] = [
                    'interface' => 'CLI-SN-' . (++$index),
                    'status' => 'online',
                    'mac_address' => null,
                    'distance' => 0,
                    'signal' => null,
                    'serial_number' => $snMatch[0],
                    'name' => null
                ];
            }
        }

        return $onus;
    }

    public function getSystemInfo()
    {
        if ($this->olt && $this->olt->snmp_community && function_exists('snmp2_get')) {
            try {
                return $this->getSystemInfoSnmp();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('HSGQ SNMP getSystemInfo failed: ' . $e->getMessage());
            }
        }

        if ($this->mode === 'http') {
            return ['uptime' => 'N/A', 'version' => 'N/A', 'cpu' => 'N/A', 'temp' => 'N/A'];
        }

        try {
            $output = $this->client->exec('show system information'); // Example command
            // Note: Command might vary by firmware version
            
            // If failed, try another
            if (str_contains(strtolower($output), 'unknown') || str_contains(strtolower($output), 'error')) {
                $output = $this->client->exec('show version');
            }

            return [
                'uptime' => $this->parseUptime($output),
                'version' => $this->parseVersion($output),
                'temp' => $this->parseTemp($output),
                'cpu' => 'N/A' // Need specific command
            ];
        } catch (\Exception $e) {
            return ['uptime' => 'Error', 'version' => 'Error', 'error' => $e->getMessage()];
        }
    }

    protected function getSystemInfoSnmp(): array
    {
        $host = $this->olt->host;
        $community = $this->olt->snmp_community;
        $port = $this->olt->snmp_port ?: 161;

        $target = $host . ':' . $port;

        $uptimeRaw = @snmp2_get($target, $community, '1.3.6.1.2.1.1.3.0');
        $descrRaw = @snmp2_get($target, $community, '1.3.6.1.2.1.1.1.0');

        if ($uptimeRaw === false && $descrRaw === false) {
            throw new \RuntimeException('SNMP query failed');
        }

        $uptime = $this->parseSnmpUptime($uptimeRaw);
        $version = $this->parseSnmpValue($descrRaw);

        return [
            'uptime' => $uptime,
            'version' => $version,
            'temp' => 'N/A',
            'cpu' => 'N/A',
        ];
    }

    protected function parseSnmpValue($value): string
    {
        if (!is_string($value)) {
            return (string) $value;
        }

        $parts = explode(':', $value, 2);
        if (count($parts) === 2) {
            return trim($parts[1]);
        }

        return trim($value);
    }

    protected function parseSnmpUptime($value): string
    {
        $str = $this->parseSnmpValue($value);

        if (preg_match('/\)\s*(.+)$/', $str, $matches)) {
            return trim($matches[1]);
        }

        return $str !== '' ? $str : 'Unknown';
    }

    protected function parseUptime($output)
    {
        if (preg_match('/uptime is (.*?)\n/i', $output, $matches)) {
            return trim($matches[1]);
        }
        return 'Unknown';
    }

    protected function parseVersion($output)
    {
        if (preg_match('/version\s+:?\s*([^\n]+)/i', $output, $matches)) {
            return trim($matches[1]);
        }
        return 'Unknown';
    }
    
    protected function parseTemp($output)
    {
        // Example: Temperature : 45 C
        if (preg_match('/temperature\s*:?\s*(\d+)/i', $output, $matches)) {
            return $matches[1] . '°C';
        }
        return 'Unknown';
    }

    public function disconnect()
    {
        try {
            $this->client->disconnect();
        } catch (\Exception $e) {
        }
    }
}
