<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramBotListener extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen for Telegram updates and process commands';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegramService)
    {
        $this->info("Starting Telegram Bot Listener...");
        // Ensure webhook is disabled for long polling
        if ($telegramService->deleteWebhook()) {
            $this->info("Deleted existing Telegram webhook (dropping pending updates).");
        }
        
        // Get initial offset to avoid processing old messages? 
        // No, let's process pending ones.
        $offset = 0;

        while (true) {
            try {
                // Polling with timeout handled in service
                $updates = $telegramService->getUpdates($offset);

                foreach ($updates as $update) {
                    $this->info("Processing update ID: " . $update['update_id']);
                    $telegramService->processMessage($update);
                    $offset = $update['update_id'] + 1;
                }
                
                // Prevent CPU spin if getUpdates returns immediately empty
                if (empty($updates)) {
                    sleep(1);
                }

            } catch (\Exception $e) {
                Log::error("Telegram Listener Error: " . $e->getMessage());
                $this->error("Error: " . $e->getMessage());
                sleep(5); // Wait before retrying
            }
        }
    }
}
