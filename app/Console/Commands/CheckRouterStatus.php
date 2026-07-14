<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Network\Services\MonitoringService;

class CheckRouterStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'router:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check router Mikrotik status, update database, and send notifications to Telegram';

    public function __construct(protected MonitoringService $monitoringService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting router status check...');

        $routers = Router::where('is_active', true)->get();

        foreach ($routers as $router) {
            $this->info("Checking router: {$router->name} ({$router->host})");
            try {
                $isOnline = $this->monitoringService->isRouterConnected($router);

                if ($isOnline) {
                    $this->info("✓ Router {$router->name} is ONLINE");
                    if ($router->is_online !== true) {
                        // Status changed from offline to online
                        $this->sendRouterStatusNotification($router, 'online');
                    }
                    $router->update([
                        'is_online' => true,
                        'last_online_at' => now()
                    ]);
                } else {
                    $this->warn("✗ Router {$router->name} is OFFLINE");
                    if ($router->is_online !== false) {
                        // Status changed from online to offline
                        $this->sendRouterStatusNotification($router, 'offline');
                    }
                    $router->update(['is_online' => false]);
                }
            } catch (\Exception $e) {
                $this->error("Error checking router {$router->name}: " . $e->getMessage());
                Log::error("Router check error for {$router->name}: " . $e->getMessage());
                if ($router->is_online !== false) {
                    $this->sendRouterStatusNotification($router, 'offline');
                }
                $router->update(['is_online' => false]);
            }
        }

        $this->info('Router status check completed.');
    }

    /**
     * Send router status notification to Telegram group.
     */
    protected function sendRouterStatusNotification(Router $router, string $status): void
    {
        try {
            $telegram = app(TelegramService::class);
            $emoji = $status === 'online' ? '🟢' : '🔴';
            $statusText = $status === 'online' ? 'ONLINE' : 'OFFLINE';

            $message = "{$emoji} *ROUTER STATUS UPDATE*\n\n";
            $message .= "*Nama:* " . TelegramService::escape($router->name) . "\n";
            $message .= "*Host:* " . TelegramService::escape($router->host) . "\n";
            $message .= "*Status:* *{$statusText}*\n";
            $message .= "*Waktu:* " . now()->format('d M Y H:i:s');

            $telegram->sendGroupNotification($message, 'router');
            $this->info("Notification sent for router {$router->name} ({$statusText})");
        } catch (\Exception $e) {
            Log::error("Failed to send router status notification: " . $e->getMessage());
            $this->error("Failed to send notification for {$router->name}: " . $e->getMessage());
        }
    }
}
