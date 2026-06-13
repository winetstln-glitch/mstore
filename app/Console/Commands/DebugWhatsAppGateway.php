<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DebugWhatsAppGateway extends Command
{
    protected $signature = 'whatsapp:debug';
    protected $description = 'Debug WhatsApp gateway connection';

    public function __construct(private WhatsAppService $whatsAppService)
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('=== WhatsApp Gateway Debug ===');
        $this->newLine();

        // Get config
        $baseUrl = config('services.whatsapp.url');
        $apiKey = config('services.whatsapp.key');

        $this->info('Base URL: ' . $baseUrl);
        $this->info('API Key: ' . substr($apiKey, 0, 8) . '...');
        $this->newLine();

        // Get server IP
        try {
            $ip = trim(Http::timeout(5)->get('https://ifconfig.me/ip')->body());
            $this->info('Server IP: ' . $ip);
            $this->warn('Pastikan IP ini di-whitelist di dashboard provider!');
        } catch (\Exception $e) {
            $this->error('Tidak bisa mendapatkan IP server: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('Testing gateway status...');
        $result = $this->whatsAppService->checkGatewayStatus();
        
        $this->newLine();
        $this->info('=== Hasil ===');
        $this->info('Connected: ' . ($result['connected'] ? '✅ Ya' : '❌ Tidak'));
        $this->info('Message: ' . $result['message']);
        if ($result['provider_response']) {
            $this->info('Provider Response: ' . $result['provider_response']);
        }

        $this->newLine();
        $this->info('Lihat log lengkap di storage/logs/laravel.log');
    }
}
