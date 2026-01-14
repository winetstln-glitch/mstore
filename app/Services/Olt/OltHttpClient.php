<?php

namespace App\Services\Olt;

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Cookie\CookieJar;

class OltHttpClient
{
    protected $baseUrl;
    protected $cookieJar;
    protected $timeout = 10;

    public function __construct()
    {
        $this->cookieJar = new CookieJar();
    }

    public function connect($host, $port = 80, $timeout = 10)
    {
        $scheme = $port == 443 ? 'https' : 'http';
        $this->baseUrl = "{$scheme}://{$host}:{$port}";
        $this->timeout = $timeout;
        
        // Simple reachability check
        try {
            $response = Http::withOptions([
                'cookies' => $this->cookieJar,
                'timeout' => $this->timeout,
                'verify' => false, // Ignore SSL for local OLTs
            ])->get($this->baseUrl);

            return true;
        } catch (\Exception $e) {
            throw new \Exception("HTTP Connection failed: " . $e->getMessage());
        }
    }

    public function get($path, $params = [])
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        
        try {
            $response = Http::withOptions([
                'cookies' => $this->cookieJar,
                'timeout' => $this->timeout,
                'verify' => false,
            ])->get($url, $params);

            return $response;
        } catch (\Exception $e) {
            throw new \Exception("HTTP GET failed: " . $e->getMessage());
        }
    }

    public function post($path, $data = [])
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');

        try {
            $response = Http::withOptions([
                'cookies' => $this->cookieJar,
                'timeout' => $this->timeout,
                'verify' => false,
            ])->asForm()->post($url, $data);

            return $response;
        } catch (\Exception $e) {
            throw new \Exception("HTTP POST failed: " . $e->getMessage());
        }
    }

    public function getCookies()
    {
        return $this->cookieJar;
    }
}
