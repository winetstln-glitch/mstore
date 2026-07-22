<?php

namespace App\Jobs;

use App\Models\Router;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DisconnectVoucherUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 120, 300];

    public function __construct(
        public Router $router,
        public string $username
    ) {}

    public function handle(): void
    {
        try {
            $client = $this->router->getMikrotikClient();
            $client->query('/ip/hotspot/active/remove')->equal('user', $this->username)->read();
            Log::info('Voucher user disconnected successfully', ['username' => $this->username, 'router' => $this->router->name]);
        } catch (\Exception $e) {
            Log::error('Failed to disconnect voucher user', [
                'username' => $this->username,
                'router' => $this->router->name,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
