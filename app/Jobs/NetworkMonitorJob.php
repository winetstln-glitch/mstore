<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Ticket;
use App\Services\GenieACSService;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NetworkMonitorJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'monitoring';

    public int $tries = 3;

    public int $timeout = 300;

    public int $uniqueFor = 600;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(GenieACSService $genieService, TelegramService $telegramService): void
    {
        $checkedCount = 0;
        $downCount = 0;
        $ticketCreatedCount = 0;
        $errorCount = 0;
        $downNotificationEnabled = filter_var(Setting::getValue('telegram_notify_ip_down', '1'), FILTER_VALIDATE_BOOL);
        $upNotificationEnabled = filter_var(Setting::getValue('telegram_notify_ip_up', '1'), FILTER_VALIDATE_BOOL);
        $stateRaw = Setting::getValue('network_monitor_device_states');
        $deviceStates = is_string($stateRaw) ? json_decode($stateRaw, true) : [];
        if (! is_array($deviceStates)) {
            $deviceStates = [];
        }

        Customer::query()
            ->select(['id', 'name', 'onu_serial'])
            ->where('status', 'active')
            ->whereNotNull('onu_serial')
            ->orderBy('id')
            ->chunkById(200, function ($customers) use ($genieService, $telegramService, $downNotificationEnabled, $upNotificationEnabled, &$deviceStates, &$checkedCount, &$downCount, &$ticketCreatedCount, &$errorCount): void {
                foreach ($customers as $customer) {
                    $result = $this->checkCustomer($customer, $genieService);
                    $checkedCount++;
                    $downCount += $result['is_down'] ? 1 : 0;
                    $ticketCreatedCount += $result['ticket_created'] ? 1 : 0;
                    $errorCount += $result['error'] ? 1 : 0;

                    if ($result['error'] ?? false) {
                        continue;
                    }

                    $stateKey = (string) $customer->id;
                    $previousState = $deviceStates[$stateKey] ?? null;
                    $previousDown = is_array($previousState) ? (bool) ($previousState['is_down'] ?? false) : null;
                    $currentDown = (bool) ($result['is_down'] ?? false);
                    $isFirstState = $previousDown === null;

                    if ($downNotificationEnabled && $currentDown && ($isFirstState || ! $previousDown)) {
                        $this->sendDownAlertToTelegram($customer, $result, $telegramService);
                    }

                    if ($upNotificationEnabled && ! $currentDown && $previousDown) {
                        $this->sendUpAlertToTelegram($customer, $result, $telegramService);
                    }

                    $deviceStates[$stateKey] = [
                        'is_down' => $currentDown,
                        'updated_at' => now()->toIso8601String(),
                        'onu_serial' => $customer->onu_serial,
                        'tr069_ip' => $result['tr069_ip'] ?? null,
                        'connection_request_url' => $result['connection_request_url'] ?? null,
                        'last_inform' => $result['last_inform'] ?? null,
                    ];
                }
            });

        $activeCustomerIds = Customer::query()
            ->where('status', 'active')
            ->whereNotNull('onu_serial')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $deviceStates = array_intersect_key($deviceStates, array_flip($activeCustomerIds));

        $summary = [
            'checked' => $checkedCount,
            'down' => $downCount,
            'tickets_created' => $ticketCreatedCount,
            'errors' => $errorCount,
            'ran_at' => now()->toIso8601String(),
        ];

        Log::info('Network monitor summary', $summary);

        $historyRaw = Setting::getValue('network_monitor_history');
        $history = is_string($historyRaw) ? json_decode($historyRaw, true) : [];
        if (! is_array($history)) {
            $history = [];
        }
        $history[] = $summary;
        $history = array_slice($history, -100);

        Setting::upsert(
            [
                [
                    'key' => 'network_monitor_summary',
                    'value' => json_encode($summary, JSON_UNESCAPED_UNICODE) ?: '{}',
                    'group' => 'system',
                    'type' => 'json',
                    'label' => 'Network Monitor Summary',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'network_monitor_history',
                    'value' => json_encode(array_values($history), JSON_UNESCAPED_UNICODE) ?: '[]',
                    'group' => 'system',
                    'type' => 'json',
                    'label' => 'Network Monitor History',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'network_monitor_device_states',
                    'value' => json_encode($deviceStates, JSON_UNESCAPED_UNICODE) ?: '{}',
                    'group' => 'system',
                    'type' => 'json',
                    'label' => 'Network Monitor Device States',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            ['key'],
            ['value', 'group', 'type', 'label', 'updated_at']
        );
        Setting::forgetCache();
    }

    protected function checkCustomer(Customer $customer, GenieACSService $genieService): array
    {
        try {
            $isDown = false;
            $reason = '';
            $ticketCreated = false;
            $tr069Ip = null;
            $connectionRequestUrl = null;
            $lastInform = null;

            if ($customer->onu_serial) {
                $onuStatus = $genieService->getDeviceStatus($customer->onu_serial);
                $tr069Ip = $onuStatus['tr069_ip'] ?? null;
                $connectionRequestUrl = $onuStatus['connection_request_url'] ?? null;
                $lastInform = $onuStatus['last_inform'] ?? null;
                if (! ($onuStatus['online'] ?? false)) {
                    $isDown = true;
                    $reason = 'ONU Offline (Last seen: '.($lastInform ?? 'Never').')';
                    if ($tr069Ip) {
                        $reason .= " [TR069 IP: {$tr069Ip}]";
                    }
                }
            }

            if ($isDown) {
                $ticketCreated = $this->createTicketIfNeeded($customer, $reason);
            }

            return [
                'is_down' => $isDown,
                'ticket_created' => $ticketCreated,
                'error' => false,
                'reason' => $reason,
                'tr069_ip' => $tr069Ip,
                'connection_request_url' => $connectionRequestUrl,
                'last_inform' => $lastInform,
            ];
        } catch (Throwable $e) {
            Log::warning('Network monitor customer check failed', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'is_down' => false,
                'ticket_created' => false,
                'error' => true,
            ];
        }
    }

    protected function createTicketIfNeeded(Customer $customer, string $reason): bool
    {
        $existingTicket = Ticket::where('customer_id', $customer->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->where('subject', 'like', 'Auto-Alert: %')
            ->exists();

        if (! $existingTicket) {
            Ticket::create([
                'customer_id' => $customer->id,
                'subject' => "Auto-Alert: Service Down - $reason",
                'description' => "System detected service interruption.\nReason: $reason\nTimestamp: ".now(),
                'status' => 'open',
                'priority' => 'high',
                // Assign to default technician or leave unassigned
            ]);

            Log::info("Auto-ticket created for customer {$customer->name} ($reason)");

            return true;
        }

        return false;
    }

    protected function sendDownAlertToTelegram(Customer $customer, array $result, TelegramService $telegramService): void
    {
        $defaultTemplate = "🚨 *ALERT MONITORING GENIEACS*\n\n".
            "*Pelanggan:* {customer_name}\n".
            "*Customer ID:* `{customer_id}`\n".
            "*SN ONU:* `{onu_serial}`\n".
            "*Status:* 🔴 OFFLINE\n".
            "*IP TR069:* {tr069_ip}\n".
            "*ConnectionRequestURL:* {connection_request_url}\n".
            "*Terakhir Inform:* {last_inform}\n".
            '*Reason:* {reason}';
        $template = Setting::getValue('telegram_ip_down_template', $defaultTemplate);
        if (! is_string($template) || trim($template) === '') {
            $template = $defaultTemplate;
        }
        $message = $this->renderTelegramTemplate($template, $this->buildTemplateData($customer, $result, '🔴 OFFLINE'));

        $sent = $telegramService->sendToTechnicianGroup($message);

        if (! $sent) {
            Log::warning('Failed sending GenieACS down alert to Telegram', [
                'customer_id' => $customer->id,
                'onu_serial' => $customer->onu_serial,
            ]);
        }
    }

    protected function sendUpAlertToTelegram(Customer $customer, array $result, TelegramService $telegramService): void
    {
        $defaultTemplate = "✅ *RECOVERY MONITORING GENIEACS*\n\n".
            "*Pelanggan:* {customer_name}\n".
            "*Customer ID:* `{customer_id}`\n".
            "*SN ONU:* `{onu_serial}`\n".
            "*Status:* 🟢 ONLINE\n".
            "*IP TR069:* {tr069_ip}\n".
            "*ConnectionRequestURL:* {connection_request_url}\n".
            "*Terakhir Inform:* {last_inform}\n".
            '*Reason:* {reason}';
        $template = Setting::getValue('telegram_ip_up_template', $defaultTemplate);
        if (! is_string($template) || trim($template) === '') {
            $template = $defaultTemplate;
        }
        $message = $this->renderTelegramTemplate($template, $this->buildTemplateData($customer, $result, '🟢 ONLINE'));

        $sent = $telegramService->sendToTechnicianGroup($message);

        if (! $sent) {
            Log::warning('Failed sending GenieACS up alert to Telegram', [
                'customer_id' => $customer->id,
                'onu_serial' => $customer->onu_serial,
            ]);
        }
    }

    protected function buildTemplateData(Customer $customer, array $result, string $status): array
    {
        $tr069Ip = $result['tr069_ip'] ?? null;
        $connectionRequestUrl = $result['connection_request_url'] ?? null;
        $lastInformRaw = $result['last_inform'] ?? null;
        $lastInformText = 'Never';

        if (is_string($lastInformRaw) && $lastInformRaw !== '') {
            try {
                $lastInformText = \Carbon\Carbon::parse($lastInformRaw)->timezone('Asia/Jakarta')->format('d M Y H:i:s');
            } catch (Throwable $e) {
                $lastInformText = $lastInformRaw;
            }
        }

        return [
            'customer_name' => $customer->name,
            'customer_id' => (string) $customer->id,
            'onu_serial' => (string) $customer->onu_serial,
            'status' => $status,
            'tr069_ip' => $tr069Ip ?: '-',
            'connection_request_url' => $connectionRequestUrl ?: '-',
            'last_inform' => $lastInformText,
            'reason' => $result['reason'] ?? ($result['is_down'] ?? false ? 'ONU Offline' : 'ONU Online'),
        ];
    }

    protected function renderTelegramTemplate(?string $template, array $data): string
    {
        $rendered = is_string($template) && trim($template) !== '' ? $template : '';
        foreach ($data as $key => $value) {
            $rendered = str_replace('{'.$key.'}', (string) $value, $rendered);
        }

        return $rendered;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Network monitor job failed', [
            'message' => $exception->getMessage(),
        ]);
    }
}
