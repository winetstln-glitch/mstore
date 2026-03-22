<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\GenieDeviceStatus;
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
use Throwable;

class NetworkMonitorJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $notifyConfig = [];

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
        $this->notifyConfig = $this->loadNotifyConfig();

        Customer::query()
            ->select(['id', 'name', 'phone', 'address', 'package', 'pppoe_user', 'status', 'onu_serial', 'created_at'])
            ->where('status', 'active')
            ->whereNotNull('onu_serial')
            ->orderBy('id')
            ->chunkById(200, function ($customers) use ($genieService, $telegramService): void {
                foreach ($customers as $customer) {
                    $this->checkCustomer($customer, $genieService, $telegramService);
                }
            });

        $this->sendPeriodicRecap($telegramService);
    }

    protected function checkCustomer(Customer $customer, GenieACSService $genieService, TelegramService $telegramService): void
    {
        try {
            $isOffline = false;
            $reason = '';
            $tr069Ip = '-';
            $connectionRequestUrl = '-';
            $lastInform = null;

            if ($customer->onu_serial) {
                $onuStatus = $genieService->getDeviceStatus($customer->onu_serial);
                $isOnlineNow = (bool) ($onuStatus['online'] ?? false);
                $isOffline = ! $isOnlineNow;
                $tr069Ip = (string) ($onuStatus['tr069_ip'] ?? '-');
                $connectionRequestUrl = (string) ($onuStatus['connection_request_url'] ?? '-');
                $lastInform = $onuStatus['last_inform'] ?? null;

                if ($isOffline) {
                    $reason = isset($onuStatus['error'])
                        ? 'ONU tidak terdeteksi ('.$onuStatus['error'].')'
                        : 'ONU Offline (Last seen: '.($lastInform ?? 'Never').')';
                }
            }

            if ($isOffline) {
                $this->createTicketIfNeeded($customer, $reason);
            }

            $statusRecord = GenieDeviceStatus::firstOrNew(['customer_id' => $customer->id]);
            $previousStatus = $statusRecord->exists ? (bool) $statusRecord->is_online : null;
            $isOnlineNow = ! $isOffline;
            $isTransitionDown = $isOffline && ($previousStatus === true || $previousStatus === null);
            $isTransitionUp = $isOnlineNow && ($previousStatus === false || $previousStatus === null);
            $isFirstOnlineForNewCustomer = $isOnlineNow
                && is_null($statusRecord->last_notified_up_at)
                && $customer->created_at
                && $customer->created_at->gte(now()->subHours(12));

            if ($isTransitionDown && ($this->notifyConfig['notify_down'] ?? false)) {
                Log::info('Network monitor transition DOWN terdeteksi', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'onu_serial' => $customer->onu_serial,
                    'tr069_ip' => $tr069Ip,
                    'last_inform' => $lastInform,
                    'reason' => $reason,
                ]);
                $payload = [
                    'customer_name' => $customer->name,
                    'customer_id' => (string) $customer->id,
                    'customer_phone' => (string) ($customer->phone ?: '-'),
                    'customer_address' => (string) ($customer->address ?: '-'),
                    'customer_package' => (string) ($customer->package ?: '-'),
                    'customer_pppoe_user' => (string) ($customer->pppoe_user ?: '-'),
                    'customer_status' => (string) ($customer->status ?: '-'),
                    'onu_serial' => (string) $customer->onu_serial,
                    'status' => '🔴 OFFLINE',
                    'tr069_ip' => $tr069Ip,
                    'connection_request_url' => $connectionRequestUrl,
                    'last_inform' => $this->formatLastInform($lastInform),
                    'reason' => $reason === '' ? '-' : $reason,
                ];
                $message = $this->renderTemplate($this->notifyConfig['down_template'], [
                    ...$payload,
                ]);
                $sent = $telegramService->sendToTechnicianGroup($message);
                if ($sent) {
                    $statusRecord->last_notified_down_at = now();
                    Log::info('Notifikasi Telegram DOWN berhasil dikirim', [
                        'customer_id' => $customer->id,
                        'onu_serial' => $customer->onu_serial,
                    ]);
                } else {
                    Log::warning('Notifikasi Telegram DOWN gagal dikirim', [
                        'customer_id' => $customer->id,
                        'onu_serial' => $customer->onu_serial,
                    ]);
                }
            }

            if (($isTransitionUp || $isFirstOnlineForNewCustomer) && ($this->notifyConfig['notify_up'] ?? false)) {
                $upReason = $isFirstOnlineForNewCustomer
                    ? 'Customer baru terdeteksi ONLINE'
                    : ($previousStatus === null ? 'Snapshot status online pertama' : 'ONU kembali online');
                Log::info('Network monitor transition UP terdeteksi', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'onu_serial' => $customer->onu_serial,
                    'tr069_ip' => $tr069Ip,
                    'last_inform' => $lastInform,
                ]);
                $payload = [
                    'customer_name' => $customer->name,
                    'customer_id' => (string) $customer->id,
                    'customer_phone' => (string) ($customer->phone ?: '-'),
                    'customer_address' => (string) ($customer->address ?: '-'),
                    'customer_package' => (string) ($customer->package ?: '-'),
                    'customer_pppoe_user' => (string) ($customer->pppoe_user ?: '-'),
                    'customer_status' => (string) ($customer->status ?: '-'),
                    'onu_serial' => (string) $customer->onu_serial,
                    'status' => '🟢 ONLINE',
                    'tr069_ip' => $tr069Ip,
                    'connection_request_url' => $connectionRequestUrl,
                    'last_inform' => $this->formatLastInform($lastInform),
                    'reason' => $upReason,
                ];
                $message = $this->renderTemplate($this->notifyConfig['up_template'], [
                    ...$payload,
                ]);
                $sent = $telegramService->sendToTechnicianGroup($message);
                if ($sent) {
                    $statusRecord->last_notified_up_at = now();
                    Log::info('Notifikasi Telegram UP berhasil dikirim', [
                        'customer_id' => $customer->id,
                        'onu_serial' => $customer->onu_serial,
                    ]);
                } else {
                    Log::warning('Notifikasi Telegram UP gagal dikirim', [
                        'customer_id' => $customer->id,
                        'onu_serial' => $customer->onu_serial,
                    ]);
                }
            }

            $statusRecord->onu_serial = (string) $customer->onu_serial;
            $statusRecord->is_online = $isOnlineNow;
            $statusRecord->last_inform = is_string($lastInform) && trim($lastInform) !== '' ? $lastInform : null;
            $statusRecord->tr069_ip = $tr069Ip !== '' ? $tr069Ip : null;
            $statusRecord->connection_request_url = $connectionRequestUrl !== '' ? $connectionRequestUrl : null;
            $statusRecord->last_reason = $reason !== '' ? $reason : null;
            $statusRecord->save();

            Log::info('Network monitor status tersimpan', [
                'customer_id' => $customer->id,
                'onu_serial' => $customer->onu_serial,
                'is_online' => $isOnlineNow,
                'is_transition_down' => $isTransitionDown,
                'is_transition_up' => $isTransitionUp,
                'is_first_online_new_customer' => $isFirstOnlineForNewCustomer,
            ]);
        } catch (Throwable $e) {
            Log::warning('Network monitor customer check failed', [
                'customer_id' => $customer->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function createTicketIfNeeded(Customer $customer, string $reason): void
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
        }
    }

    protected function loadNotifyConfig(): array
    {
        $defaultDownTemplate = "🚨 *ALERT MONITORING GENIEACS*\n\n".
            "*Pelanggan:* {customer_name}\n".
            "*Customer ID:* `{customer_id}`\n".
            "*No HP:* {customer_phone}\n".
            "*Alamat:* {customer_address}\n".
            "*Paket:* {customer_package}\n".
            "*PPPoE User:* `{customer_pppoe_user}`\n".
            "*Status Pelanggan:* {customer_status}\n".
            "*SN ONU:* `{onu_serial}`\n".
            "*Status:* 🔴 OFFLINE\n".
            "*IP TR069:* {tr069_ip}\n".
            "*ConnectionRequestURL:* {connection_request_url}\n".
            "*Terakhir Inform:* {last_inform}\n".
            '*Reason:* {reason}';

        $defaultUpTemplate = "✅ *RECOVERY MONITORING GENIEACS*\n\n".
            "*Pelanggan:* {customer_name}\n".
            "*Customer ID:* `{customer_id}`\n".
            "*No HP:* {customer_phone}\n".
            "*Alamat:* {customer_address}\n".
            "*Paket:* {customer_package}\n".
            "*PPPoE User:* `{customer_pppoe_user}`\n".
            "*Status Pelanggan:* {customer_status}\n".
            "*SN ONU:* `{onu_serial}`\n".
            "*Status:* 🟢 ONLINE\n".
            "*IP TR069:* {tr069_ip}\n".
            "*ConnectionRequestURL:* {connection_request_url}\n".
            "*Terakhir Inform:* {last_inform}\n".
            '*Reason:* {reason}';

        $notifyDown = Setting::getValue('telegram_notify_ip_down', '1') === '1';
        $notifyUp = Setting::getValue('telegram_notify_ip_up', '1') === '1';
        $downTemplate = (string) Setting::getValue('telegram_ip_down_template', $defaultDownTemplate);
        $upTemplate = (string) Setting::getValue('telegram_ip_up_template', $defaultUpTemplate);

        return [
            'notify_down' => $notifyDown,
            'notify_up' => $notifyUp,
            'down_template' => trim($downTemplate) !== '' ? $downTemplate : $defaultDownTemplate,
            'up_template' => trim($upTemplate) !== '' ? $upTemplate : $defaultUpTemplate,
        ];
    }

    protected function renderTemplate(string $template, array $data): string
    {
        $rendered = $template;
        foreach ($data as $key => $value) {
            $rendered = str_replace('{'.$key.'}', (string) $value, $rendered);
        }

        return $rendered;
    }

    protected function formatLastInform(?string $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('d M Y H:i:s');
        } catch (Throwable $e) {
            return $value;
        }
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

    protected function sendPeriodicRecap(TelegramService $telegramService): void
    {
        $enabled = Setting::getValue('telegram_notify_monitor_recap', '1') === '1';
        if (! $enabled) {
            return;
        }

        $intervalMinutes = (int) Setting::getValue('telegram_monitor_recap_interval_minutes', '30');
        if ($intervalMinutes < 5) {
            $intervalMinutes = 5;
        }

        $lastSentAt = Setting::getValue('telegram_monitor_recap_last_sent_at');
        if (is_string($lastSentAt) && trim($lastSentAt) !== '') {
            try {
                if (Carbon::parse($lastSentAt)->gt(now()->subMinutes($intervalMinutes))) {
                    return;
                }
            } catch (Throwable $e) {
            }
        }

        $customerIds = Customer::query()
            ->where('status', 'active')
            ->whereNotNull('onu_serial')
            ->pluck('id');
        $total = $customerIds->count();
        if ($total === 0) {
            return;
        }

        $online = GenieDeviceStatus::query()
            ->whereIn('customer_id', $customerIds)
            ->where('is_online', true)
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->count();
        $offlineDirect = GenieDeviceStatus::query()
            ->whereIn('customer_id', $customerIds)
            ->where('is_online', false)
            ->count();
        $offlineStaleOnline = GenieDeviceStatus::query()
            ->whereIn('customer_id', $customerIds)
            ->where('is_online', true)
            ->where('updated_at', '<', now()->subMinutes(15))
            ->count();
        $offline = $offlineDirect + $offlineStaleOnline;
        $unknown = max(0, $total - $online - $offline);

        $message = "📊 *Rekap Monitor GenieACS*\n\n";
        $message .= "*Total Modem Aktif:* {$total}\n";
        $message .= "*Online:* 🟢 {$online}\n";
        $message .= "*Offline:* 🔴 {$offline}\n";
        $message .= "*Belum Sinkron:* ⚪ {$unknown}\n";
        $message .= '*Waktu:* '.now()->format('d M Y H:i:s');

        $sent = $telegramService->sendToTechnicianGroup($message);
        if ($sent) {
            Setting::updateOrCreate(
                ['key' => 'telegram_monitor_recap_last_sent_at'],
                [
                    'value' => now()->toDateTimeString(),
                    'group' => 'telegram',
                    'type' => 'text',
                    'label' => 'Telegram monitor recap last sent at',
                ]
            );
        }
    }
}
