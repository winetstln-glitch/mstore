<?php

namespace App\Console\Commands;

use App\Models\GenieDeviceStatus;
use App\Services\GenieACSService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorGenieDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'genieacs:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor GenieACS devices status and send Telegram notifications on change';

    /**
     * Execute the console command.
     */
    public function handle(GenieACSService $genieService)
    {
        $this->info("Starting GenieACS Device Monitoring...");

        $devices = $genieService->getAllDevicesForMonitoring();
        $this->info("Fetched " . count($devices) . " devices.");

        foreach ($devices as $device) {
            $deviceId = $device['_id'] ?? null;
            if (!$deviceId) continue;

            $lastInformStr = $device['_lastInform'] ?? null;
            $lastInform = $lastInformStr ? Carbon::parse($lastInformStr) : null;
            
            // Determine IP (try different paths)
            $ipRaw = $device['InternetGatewayDevice']['WANDevice']['1']['WANConnectionDevice']['1']['WANPPPConnection']['1']['ExternalIPAddress'] 
                ?? $device['InternetGatewayDevice']['WANDevice']['1']['WANConnectionDevice']['1']['WANIPConnection']['1']['ExternalIPAddress']
                ?? $device['Device']['IP']['Interface']['1']['IPv4Address']['1']['IPAddress']
                ?? null;

            // Handle if IP is returned as array (e.g. {_value: "...", _timestamp: ...})
            $ip = is_array($ipRaw) ? ($ipRaw['_value'] ?? null) : $ipRaw;

            // Definition of Online: Last Inform < 10 minutes ago
            $isOnline = false;
            if ($lastInform && $lastInform->diffInMinutes(now()) <= 10) {
                $isOnline = true;
            }

            // Check existing status
            $statusRecord = GenieDeviceStatus::firstOrNew(['device_id' => $deviceId]);
            
            // Check for changes
            $wasOnline = $statusRecord->exists ? $statusRecord->is_online : null; // null for new record
            
            // Save current state
            $statusRecord->ip_address = $ip;
            $statusRecord->last_inform = $lastInform;
            $statusRecord->is_online = $isOnline;
            $statusRecord->save();

            // Send Notification if status changed
            // Only send if it's NOT a new record (to avoid spam on first run) OR if we want initial status?
            // Usually we only want changes. If $wasOnline is null, it's new.
            if ($wasOnline !== null && $wasOnline !== $isOnline) {
                $statusText = $isOnline ? "ONLINE 🟢" : "OFFLINE 🔴";
                $message = "📡 *GenieACS Device Alert*\n\n"
                    . "Device: `{$deviceId}`\n"
                    . "Status: {$statusText}\n"
                    . "IP: `{$ip}`\n"
                    . "Time: " . now()->format('Y-m-d H:i:s');
                
                $this->sendTelegram($message);
                $this->info("Sent alert for {$deviceId}: {$statusText}");
            }
        }

        $this->info("Monitoring finished.");
    }

    private function sendTelegram($message)
    {
        $token = config('services.telegram.bot_token');
        // Prefer env for chat_id, or hardcoded if necessary
        $chatId = env('TELEGRAM_CHAT_ID');

        if (!$token || !$chatId) {
            Log::warning("Telegram credentials missing. Token: " . ($token ? 'OK' : 'MISSING') . ", ChatID: " . ($chatId ? 'OK' : 'MISSING'));
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram: " . $e->getMessage());
        }
    }
}
