<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Ticket;
use App\Services\GenieACSService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class NetworkMonitorJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $uniqueFor = 600;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('monitoring');
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
        $downAlertSentCount = 0;
        $upAlertSentCount = 0;
        $alertFailedCount = 0;
        $retryAlertSentCount = 0;
        $retryAlertFailedCount = 0;
        $hasOntSnColumn = Schema::hasColumn('customers', 'ont_sn');
        $downNotificationEnabled = filter_var(Setting::getValue('telegram_notify_ip_down', '1'), FILTER_VALIDATE_BOOL);
        $upNotificationEnabled = filter_var(Setting::getValue('telegram_notify_ip_up', '1'), FILTER_VALIDATE_BOOL);
        $downConfirmChecks = $this->sanitizePositiveInt(Setting::getValue('network_monitor_down_confirm_checks', '2'), 2, 1, 10);
        $upConfirmChecks = $this->sanitizePositiveInt(Setting::getValue('network_monitor_up_confirm_checks', '2'), 2, 1, 10);
        $maxRetryAttempts = $this->sanitizePositiveInt(Setting::getValue('network_monitor_telegram_max_retry_attempts', '5'), 5, 1, 20);
        $retryBackoffMinutes = $this->sanitizePositiveInt(Setting::getValue('network_monitor_telegram_retry_backoff_minutes', '5'), 5, 1, 120);
        $maxRetryQueueSize = $this->sanitizePositiveInt(Setting::getValue('network_monitor_telegram_retry_queue_size', '1000'), 1000, 100, 5000);
        $stateRaw = Setting::getValue('network_monitor_device_states');
        $deviceStates = is_string($stateRaw) ? json_decode($stateRaw, true) : [];
        if (! is_array($deviceStates)) {
            $deviceStates = [];
        }
        $retryQueueRaw = Setting::getValue('network_monitor_telegram_retry_queue');
        $retryQueue = is_string($retryQueueRaw) ? json_decode($retryQueueRaw, true) : [];
        if (! is_array($retryQueue)) {
            $retryQueue = [];
        }

        $customerQuery = Customer::query()
            ->select(['id', 'name', 'onu_serial'])
            ->where('status', 'active')
            ->orderBy('id')
            ->where(function ($query) use ($hasOntSnColumn): void {
                $query->whereNotNull('onu_serial');
                if ($hasOntSnColumn) {
                    $query->orWhereNotNull('ont_sn');
                }
            });

        if ($hasOntSnColumn) {
            $customerQuery->addSelect('ont_sn');
        }

        $customerQuery->chunkById(200, function ($customers) use (
            $genieService,
            $telegramService,
            $downNotificationEnabled,
            $upNotificationEnabled,
            $downConfirmChecks,
            $upConfirmChecks,
            &$deviceStates,
            &$retryQueue,
            &$checkedCount,
            &$downCount,
            &$ticketCreatedCount,
            &$errorCount,
            &$downAlertSentCount,
            &$upAlertSentCount,
            &$alertFailedCount
        ): void {
            foreach ($customers as $customer) {
                $serialNumber = $this->resolveSerialNumber($customer);
                if ($serialNumber === null) {
                    continue;
                }

                $result = $this->checkCustomer($customer, $genieService, $serialNumber);
                $checkedCount++;
                $ticketCreatedCount += $result['ticket_created'] ? 1 : 0;
                $errorCount += $result['error'] ? 1 : 0;

                if ($result['error'] ?? false) {
                    continue;
                }

                $stateKey = (string) $customer->id;
                $previousState = $deviceStates[$stateKey] ?? null;
                $previousStableDown = is_array($previousState) ? (bool) ($previousState['is_down'] ?? false) : null;
                $currentRawDown = (bool) ($result['is_down'] ?? false);
                $isFirstState = $previousStableDown === null;
                $consecutiveDownChecks = is_array($previousState) ? (int) ($previousState['consecutive_down_checks'] ?? 0) : 0;
                $consecutiveUpChecks = is_array($previousState) ? (int) ($previousState['consecutive_up_checks'] ?? 0) : 0;
                if ($currentRawDown) {
                    $consecutiveDownChecks++;
                    $consecutiveUpChecks = 0;
                } else {
                    $consecutiveUpChecks++;
                    $consecutiveDownChecks = 0;
                }
                $currentStableDown = $isFirstState ? $currentRawDown : $previousStableDown;
                if (! $isFirstState && $currentRawDown !== $previousStableDown) {
                    if ($currentRawDown && $consecutiveDownChecks >= $downConfirmChecks) {
                        $currentStableDown = true;
                    }
                    if (! $currentRawDown && $consecutiveUpChecks >= $upConfirmChecks) {
                        $currentStableDown = false;
                    }
                }
                $statusChanged = $isFirstState ? true : ($previousStableDown !== $currentStableDown);
                $downAlertSent = false;
                $upAlertSent = false;

                if ($downNotificationEnabled && ! $isFirstState && $statusChanged && $currentStableDown) {
                    $downAlert = $this->sendDownAlertToTelegram($customer, $result, $telegramService);
                    $downAlertSent = $downAlert['sent'] ?? false;
                    if ($downAlertSent) {
                        $downAlertSentCount++;
                    } else {
                        $retryQueue[] = $this->buildRetryQueueItem($customer, 'down', $downAlert['message'] ?? '');
                        $alertFailedCount++;
                    }
                }

                if ($upNotificationEnabled && ! $isFirstState && $statusChanged && ! $currentStableDown) {
                    $upAlert = $this->sendUpAlertToTelegram($customer, $result, $telegramService);
                    $upAlertSent = $upAlert['sent'] ?? false;
                    if ($upAlertSent) {
                        $upAlertSentCount++;
                    } else {
                        $retryQueue[] = $this->buildRetryQueueItem($customer, 'up', $upAlert['message'] ?? '');
                        $alertFailedCount++;
                    }
                }

                $downCount += $currentStableDown ? 1 : 0;

                $deviceStates[$stateKey] = [
                    'is_down' => $currentStableDown,
                    'raw_is_down' => $currentRawDown,
                    'updated_at' => now()->toIso8601String(),
                    'status_changed_at' => $statusChanged
                        ? now()->toIso8601String()
                        : (is_array($previousState) ? ($previousState['status_changed_at'] ?? now()->toIso8601String()) : now()->toIso8601String()),
                    'last_down_alert_at' => $downAlertSent
                        ? now()->toIso8601String()
                        : (is_array($previousState) ? ($previousState['last_down_alert_at'] ?? null) : null),
                    'last_up_alert_at' => $upAlertSent
                        ? now()->toIso8601String()
                        : (is_array($previousState) ? ($previousState['last_up_alert_at'] ?? null) : null),
                    'onu_serial' => $serialNumber,
                    'tr069_ip' => $result['tr069_ip'] ?? null,
                    'connection_request_url' => $result['connection_request_url'] ?? null,
                    'last_inform' => $result['last_inform'] ?? null,
                    'last_inform_age_seconds' => $result['last_inform_age_seconds'] ?? null,
                    'consecutive_down_checks' => $consecutiveDownChecks,
                    'consecutive_up_checks' => $consecutiveUpChecks,
                ];
            }
        });

        $activeCustomersQuery = Customer::query()
            ->where('status', 'active')
            ->where(function ($query) use ($hasOntSnColumn): void {
                $query->whereNotNull('onu_serial');
                if ($hasOntSnColumn) {
                    $query->orWhereNotNull('ont_sn');
                }
            });
        $activeCustomerIds = $activeCustomersQuery->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $deviceStates = array_intersect_key($deviceStates, array_flip($activeCustomerIds));
        $retryQueue = $this->processTelegramRetryQueue(
            $retryQueue,
            $deviceStates,
            $telegramService,
            $maxRetryAttempts,
            $retryBackoffMinutes,
            $maxRetryQueueSize,
            $downAlertSentCount,
            $upAlertSentCount,
            $retryAlertSentCount,
            $retryAlertFailedCount
        );

        $summary = [
            'checked' => $checkedCount,
            'down' => $downCount,
            'tickets_created' => $ticketCreatedCount,
            'errors' => $errorCount,
            'down_alerts_sent' => $downAlertSentCount,
            'up_alerts_sent' => $upAlertSentCount,
            'alert_send_failed' => $alertFailedCount,
            'retry_alerts_sent' => $retryAlertSentCount,
            'retry_alerts_failed' => $retryAlertFailedCount,
            'retry_queue_pending' => count($retryQueue),
            'down_confirm_checks' => $downConfirmChecks,
            'up_confirm_checks' => $upConfirmChecks,
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
                [
                    'key' => 'network_monitor_telegram_retry_queue',
                    'value' => json_encode(array_values($retryQueue), JSON_UNESCAPED_UNICODE) ?: '[]',
                    'group' => 'system',
                    'type' => 'json',
                    'label' => 'Network Monitor Telegram Retry Queue',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            ['key'],
            ['value', 'group', 'type', 'label', 'updated_at']
        );
        Setting::forgetCache();
    }

    protected function checkCustomer(Customer $customer, GenieACSService $genieService, ?string $serialNumber = null): array
    {
        try {
            $isDown = false;
            $reason = '';
            $ticketCreated = false;
            $tr069Ip = null;
            $connectionRequestUrl = null;
            $lastInform = null;
            $lastInformAgeSeconds = null;
            $serial = $serialNumber ?: $this->resolveSerialNumber($customer);

            if ($serial) {
                $onuStatus = $genieService->getDeviceStatus($serial);
                $tr069Ip = $onuStatus['tr069_ip'] ?? null;
                $connectionRequestUrl = $onuStatus['connection_request_url'] ?? null;
                $lastInform = $onuStatus['last_inform'] ?? null;
                $lastInformAgeSeconds = isset($onuStatus['last_inform_age_seconds']) ? (int) $onuStatus['last_inform_age_seconds'] : null;
                if (! ($onuStatus['online'] ?? false)) {
                    $isDown = true;
                    $reason = 'ONU Offline (Last seen: '.($lastInform ?? 'Never').')';
                    if (is_int($lastInformAgeSeconds) && $lastInformAgeSeconds >= 0) {
                        $reason .= ' [Age: '.$this->formatDurationSeconds($lastInformAgeSeconds).']';
                    }
                    if ($tr069Ip) {
                        $reason .= " [TR069 IP: {$tr069Ip}]";
                    }
                } else {
                    $reason = 'ONU Online';
                    if (is_int($lastInformAgeSeconds) && $lastInformAgeSeconds >= 0) {
                        $reason .= ' [Age: '.$this->formatDurationSeconds($lastInformAgeSeconds).']';
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
                'last_inform_age_seconds' => $lastInformAgeSeconds ?? null,
            ];
        } catch (Throwable $e) {
            Log::warning('Network monitor customer check failed', [
                'customer_id' => $customer->id,
                'onu_serial' => $this->resolveSerialNumber($customer),
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

    protected function sendDownAlertToTelegram(Customer $customer, array $result, TelegramService $telegramService): array
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
                'onu_serial' => $this->resolveSerialNumber($customer),
            ]);
        }

        return [
            'sent' => $sent,
            'message' => $message,
        ];
    }

    protected function sendUpAlertToTelegram(Customer $customer, array $result, TelegramService $telegramService): array
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
                'onu_serial' => $this->resolveSerialNumber($customer),
            ]);
        }

        return [
            'sent' => $sent,
            'message' => $message,
        ];
    }

    protected function buildTemplateData(Customer $customer, array $result, string $status): array
    {
        $serialNumber = $this->resolveSerialNumber($customer);
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
            'onu_serial' => (string) ($serialNumber ?: '-'),
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

    protected function resolveSerialNumber(Customer $customer): ?string
    {
        $serial = $customer->onu_serial ?? null;
        if (is_string($serial) && trim($serial) !== '') {
            return trim($serial);
        }

        $legacySerial = $customer->ont_sn ?? null;
        if (is_string($legacySerial) && trim($legacySerial) !== '') {
            return trim($legacySerial);
        }

        return null;
    }

    protected function sanitizePositiveInt(mixed $rawValue, int $default, int $min, int $max): int
    {
        if (! is_numeric($rawValue)) {
            return $default;
        }

        $value = (int) $rawValue;
        if ($value < $min) {
            return $min;
        }
        if ($value > $max) {
            return $max;
        }

        return $value;
    }

    protected function buildRetryQueueItem(Customer $customer, string $state, string $message): array
    {
        return [
            'customer_id' => (string) $customer->id,
            'state' => $state,
            'message' => $message,
            'attempts' => 0,
            'next_attempt_at' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
            'last_error_at' => now()->toIso8601String(),
        ];
    }

    protected function processTelegramRetryQueue(
        array $retryQueue,
        array $deviceStates,
        TelegramService $telegramService,
        int $maxRetryAttempts,
        int $retryBackoffMinutes,
        int $maxRetryQueueSize,
        int &$downAlertSentCount,
        int &$upAlertSentCount,
        int &$retryAlertSentCount,
        int &$retryAlertFailedCount
    ): array {
        $now = now();
        $processed = [];
        foreach ($retryQueue as $item) {
            if (! is_array($item)) {
                continue;
            }
            $message = $item['message'] ?? null;
            $state = $item['state'] ?? null;
            if (! is_string($message) || trim($message) === '' || ! in_array($state, ['down', 'up'], true)) {
                continue;
            }

            $attempts = isset($item['attempts']) ? (int) $item['attempts'] : 0;
            if ($attempts >= $maxRetryAttempts) {
                continue;
            }

            $nextAttemptAt = $item['next_attempt_at'] ?? null;
            $nextAttempt = null;
            if (is_string($nextAttemptAt) && trim($nextAttemptAt) !== '') {
                try {
                    $nextAttempt = Carbon::parse($nextAttemptAt);
                } catch (Throwable $e) {
                    $nextAttempt = null;
                }
            }
            if ($nextAttempt instanceof Carbon && $nextAttempt->gt($now)) {
                $processed[] = $item;

                continue;
            }

            $customerId = (string) ($item['customer_id'] ?? '');
            $deviceState = $deviceStates[$customerId] ?? null;
            $currentDown = is_array($deviceState) ? (bool) ($deviceState['is_down'] ?? false) : null;
            if ($currentDown !== null) {
                if ($state === 'down' && ! $currentDown) {
                    continue;
                }
                if ($state === 'up' && $currentDown) {
                    continue;
                }
            }

            $sent = $telegramService->sendToTechnicianGroup($message);
            if ($sent) {
                $retryAlertSentCount++;
                if ($state === 'down') {
                    $downAlertSentCount++;
                } else {
                    $upAlertSentCount++;
                }

                continue;
            }

            $retryAlertFailedCount++;
            $attempts++;
            if ($attempts >= $maxRetryAttempts) {
                continue;
            }
            $item['attempts'] = $attempts;
            $item['last_error_at'] = $now->toIso8601String();
            $item['next_attempt_at'] = $now->copy()->addMinutes($retryBackoffMinutes * max(1, $attempts))->toIso8601String();
            $processed[] = $item;
        }

        if (count($processed) > $maxRetryQueueSize) {
            $processed = array_slice($processed, -$maxRetryQueueSize);
        }

        return array_values($processed);
    }

    protected function formatDurationSeconds(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;
        if ($hours > 0) {
            return "{$hours}h {$minutes}m {$remainingSeconds}s";
        }
        if ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        return "{$remainingSeconds}s";
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
