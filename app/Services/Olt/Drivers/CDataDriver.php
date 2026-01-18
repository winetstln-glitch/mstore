<?php

namespace App\Services\Olt\Drivers;

use App\Services\Olt\OltDriverInterface;
use App\Services\Olt\TelnetClient;
use App\Services\Olt\OltHttpClient;
use App\Models\Olt;

class CDataDriver implements OltDriverInterface
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
            $this->client->login(
                $olt->username, 
                $olt->password, 
                ['Username:', 'Login:', 'User Name:', 'user:', 'username:', 'login:'], 
                ['Password:', 'Pass:', 'password:', 'pass:']
            );
            
            // Wait for successful login prompt
            $this->client->waitPrompt(['#', '>', 'OLT>', 'EPON#', 'GPON#', '$']);

            $this->client->setPrompt(['#', '>', 'OLT>', 'EPON#', 'GPON#', '$']); 
            
            // C-Data often drops directly to enable mode or needs 'enable'
            $this->client->write('enable');
             try {
                $buffer = $this->client->waitPrompt(['Password:', 'Pass:', 'password:', 'pass:', '#', '>', 'OLT>', 'EPON#', 'GPON#', '$']);
                if (stripos($buffer, 'Password:') !== false || stripos($buffer, 'Pass:') !== false) {
                    $this->client->write($olt->password);
                    $this->client->waitPrompt(['#', '>', 'OLT>', 'EPON#', 'GPON#', '$']);
                }
            } catch (\Exception $e) {
                // Ignore
            }

            // Disable pagination
            try {
                $this->client->write('terminal length 0');
                $this->client->waitPrompt(['#', '>', 'OLT>', 'EPON#', 'GPON#', '$']);
            } catch (\Exception $e) {
                // Ignore
            }

        } catch (\Exception $e) {
            throw new \Exception("Login failed: " . $e->getMessage());
        }
    }

    protected function loginHttp($username, $password)
    {
        // Try common C-Data login paths
        $paths = [
            '/login.cgi',
            '/goform/login',
            '/admin/login.asp',
            '/cgi-bin/login.cgi'
        ];

        $success = false;
        $lastError = '';

        foreach ($paths as $path) {
            try {
                \Illuminate\Support\Facades\Log::info("C-Data HTTP Login Attempt: {$path}");
                
                // C-Data often uses a simple POST
                $response = $this->httpClient->post($path, [
                    'username' => $username,
                    'password' => $password,
                    'Action' => 'Login', // Common in C-Data
                    'key' => rand(1000, 9999), // Some versions require a random key
                ]);

                \Illuminate\Support\Facades\Log::info("C-Data HTTP Login Response: {$response->status()}");

                if ($response->status() === 200 || $response->status() === 302) {
                    $success = true;
                    break;
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                \Illuminate\Support\Facades\Log::error("C-Data HTTP Login Error: {$e->getMessage()}");
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
            'show epon onu state',
            'show epon onu all',
            'show gpon onu-information',
            'show gpon onu state',
            'show gpon onu all',
            'show onu all',
        ];

        $output = '';
        foreach ($commands as $cmd) {
            try {
                $result = $this->client->exec($cmd);
                if (!str_contains(strtolower($result), 'unknown') && !str_contains(strtolower($result), 'error')) {
                    $output .= "\n" . $result;
                }
            } catch (\Exception $e) {
            }
        }

        return $this->parseOnuOutput($output);
    }

    protected function getOnusHttp()
    {
         // C-Data Web UI for ONUs is usually in a frame or fetched via AJAX
         $paths = [
             '/onu_list.cgi', 
             '/onu_info.asp', 
             '/admin/onu_list.asp',
             '/epon/onu_list.asp',
             '/cgi-bin/onu_list.cgi',
             '/cgi-bin/onu_info.cgi'
         ];
         
         foreach ($paths as $path) {
            try {
                \Illuminate\Support\Facades\Log::info("C-Data HTTP Fetch Attempt: {$path}");
                $response = $this->httpClient->get($path);
                
                \Illuminate\Support\Facades\Log::info("C-Data HTTP Fetch Response: {$response->status()} - Length: " . strlen($response->body()));

                if ($response->successful() && strlen($response->body()) > 100) {
                     // Very rough parsing
                     $onus = $this->parseOnuHtml($response->body());
                     if (count($onus) > 0) {
                         return $onus;
                     } else {
                         \Illuminate\Support\Facades\Log::warning("C-Data HTTP Parsed 0 ONUs from {$path}. Content preview: " . substr($response->body(), 0, 500));
                     }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("C-Data HTTP Fetch Error: {$e->getMessage()}");
                continue;
            }
        }
        
        throw new \Exception("HTTP Fetch failed. Could not find valid ONU list page. Check logs for details.");
    }
    
    protected function parseOnuHtml($html)
    {
         // Basic MAC finding for C-Data
         $onus = [];
         preg_match_all('/([0-9a-fA-F]{2}[:.-]?){5}[0-9a-fA-F]{2}/', $html, $matches);
         
         if (!empty($matches[0])) {
            foreach (array_unique($matches[0]) as $index => $mac) {
                $mac = str_replace(['-', '.'], ':', $mac);
                $onus[] = [
                    'interface' => 'WEB-' . ($index + 1),
                    'status' => 'online',
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
            // Skip headers
            if (empty($line) || str_starts_with($line, 'Interface') || str_starts_with($line, 'UNI')) {
                continue;
            }

            // C-Data Format 1: EPON0/1:1  Online  00:11:22...
            // C-Data Format 2: 0/1/1      Online  ...
            // Regex to catch interface, status, mac
            if (preg_match('/^((?:EPON|GPON|XPON)?\d+\/\d+(?:[:\/]\d+)?)\s+(\S+)\s+([0-9a-fA-F:.]+)/', $line, $matches)) {
                $status = strtolower($matches[2]);
                $dbStatus = 'offline';

                if (in_array($status, ['online', 'up', 'working'])) {
                    $dbStatus = 'online';
                } elseif (in_array($status, ['offline', 'down', 'dying-gasp'])) {
                    $dbStatus = 'offline';
                } elseif (str_contains($status, 'los')) {
                    $dbStatus = 'los';
                }

                $onus[] = [
                    'interface' => $matches[1],
                    'status' => $dbStatus,
                    'mac_address' => $matches[3],
                    'distance' => null, // C-Data usually needs 'show onu distance'
                    'signal' => null,   // C-Data usually needs 'show onu optical'
                    'serial_number' => null,
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
            $output = $this->client->exec('show version'); 
            
            return [
                'uptime' => $this->parseUptime($output),
                'version' => $this->parseVersion($output),
                'temp' => 'N/A',
                'cpu' => 'N/A'
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
        if (preg_match('/version\s*:?\s*([^\n]+)/i', $output, $matches)) {
            return trim($matches[1]);
        }
        return 'Unknown';
    }

    public function disconnect()
    {
        if ($this->mode === 'telnet') {
            $this->client->disconnect();
        }
    }
}
