<?php

namespace App\Services\Olt\Drivers;

use App\Services\Olt\OltDriverInterface;
use App\Services\Olt\TelnetClient;
use App\Services\Olt\OltHttpClient;
use App\Models\Olt;

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
        
        // Detect mode based on port
        if (in_array($olt->port, [80, 443, 8080])) {
            $this->mode = 'http';
            $this->httpClient->connect($olt->host, $olt->port, $timeout);
            $this->loginHttp($olt->username, $olt->password);
        } else {
            $this->mode = 'telnet';
            $this->connectTelnet($olt, $timeout);
        }
    }

    protected function connectTelnet(Olt $olt, $timeout)
    {
        $this->client->connect($olt->host, $olt->port, $timeout);
        
        try {
            // HSGQ Prompts can vary
            $this->client->login(
                $olt->username, 
                $olt->password, 
                ['Login:', 'User Name:', 'user:', 'User:', 'Username:', 'username:', 'login:', 'Login'], 
                ['Password:', 'Pass:', 'password:', 'pass:']
            );

            // Wait for successful login prompt
            $this->client->waitPrompt(['>', '#', '$']);

            // Determine prompt - usually ends with > or #
            $this->client->setPrompt(['>', '#', '$']); 
            
            // Enable admin mode if needed
            $this->client->write('enable');
            
            // Wait for password prompt OR prompt symbol
            try {
                $buffer = $this->client->waitPrompt(['Password:', 'Pass:', 'password:', 'pass:', '#', '>', '$']);
                if (stripos($buffer, 'Password:') !== false || stripos($buffer, 'Pass:') !== false) {
                    $this->client->write($olt->password);
                    $this->client->waitPrompt(['#', '>', '$']);
                }
            } catch (\Exception $e) {
                // Ignore
            }

            $this->client->setPrompt(['#', '>', '$']); 
            
            // Disable pagination
            try {
                $this->client->write('terminal length 0');
                $this->client->waitPrompt(['#', '>', '$']);
            } catch (\Exception $e) {
                // Ignore
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
        if ($this->mode === 'http') {
            return $this->getOnusHttp();
        }
        
        return $this->getOnusTelnet();
    }
    
    protected function getOnusTelnet()
    {
        $commands = [
            'show epon onu-information',
            'show gpon onu information',
            'show onu all'
        ];
        
        $output = '';
        foreach ($commands as $cmd) {
            try {
                $result = $this->client->exec($cmd);
                if (!str_contains(strtolower($result), 'unknown') && !str_contains(strtolower($result), 'error')) {
                    $output .= "\n" . $result;
                }
            } catch (\Exception $e) {
                // Ignore command errors
            }
        }
        
        return $this->parseOnuOutput($output);
    }
    
    
    protected function getOnusHttp()
    {
        // Try to fetch ONU list page
        // Added more paths for better coverage
        $paths = [
            '/onu_allow_list.asp', 
            '/onu_list.asp', 
            '/epon/onu_list.asp', 
            '/gpon/onu_list.asp',
            '/onu_index.asp',
            '/device/onu_list.asp',
            '/admin/onu_info.asp'
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
        
        throw new \Exception("HTTP Fetch failed. Could not find valid ONU list page. Check logs for details.");
    }
    
    protected function parseOnuHtml($html)
    {
        $onus = [];
        
        // Regex to find MAC addresses in table cells
        // This is a best-effort parser for HTML tables containing MACs
        preg_match_all('/([0-9a-fA-F]{2}[:.-]?){5}[0-9a-fA-F]{2}/', $html, $matches);
        
        if (!empty($matches[0])) {
            foreach (array_unique($matches[0]) as $index => $mac) {
                // Normalize MAC
                $mac = str_replace(['-', '.'], ':', $mac);
                
                // Try to find context (interface) if possible, but regex is hard on raw HTML
                // For now, we generate a pseudo-interface or use index
                
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
        
        return $onus;
    }

    protected function parseOnuOutput($output)
    {
        $onus = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, 'Interface') || str_starts_with($line, 'OnuIndex')) {
                continue;
            }

            // Regex for HSGQ standard output: Interface Status MAC [Distance]
            // Matches: EPON0/1:1 Register 00:11:22:33:44:55 10
            // Or: EPON0/1:1 Online 00:11:22:33:44:55
            // Also supports variation with slashes or colons
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
            }
        }

        return $onus;
    }

    public function getSystemInfo()
    {
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
        if ($this->mode === 'telnet') {
            try {
                $this->client->disconnect();
            } catch (\Exception $e) {
                // Ignore
            }
        }
    }
}
