<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $botToken;

    protected $apiUrl;

    public function __construct()
    {
        $setting = Setting::where('key', 'telegram_bot_token')->first();
        $this->botToken = $setting ? $setting->value : config('services.telegram.bot_token');

        if (empty($this->botToken)) {
            $this->botToken = env('TELEGRAM_BOT_TOKEN');
        }

        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
    }

    public function getUpdates($offset = 0)
    {
        if (empty($this->botToken)) {
            return [];
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/getUpdates";
        try {
            $response = Http::timeout(60)->get($url, [
                'offset' => $offset,
                'timeout' => 50, // Long polling timeout
            ]);

            if ($response->successful()) {
                return $response->json()['result'] ?? [];
            } else {
                // Check for 409 Conflict (Webhook is set)
                if ($response->status() === 409) {
                    Log::warning('Telegram getUpdates Conflict: Webhook is set. Deleting webhook...');
                    $this->deleteWebhook();

                    return [];
                }
                Log::error('Telegram API Error: '.$response->body());
            }
        } catch (\Exception $e) {
            // Long-polling timeouts (cURL error 28) are expected when no messages arrive
            $msg = $e->getMessage();
            if (str_contains($msg, 'cURL error 28')) {
                Log::notice('Telegram getUpdates timeout (no updates).');
            } else {
                Log::error('Telegram getUpdates Error: '.$msg);
            }
        }

        return [];
    }

    public function deleteWebhook()
    {
        if (empty($this->botToken)) {
            return false;
        }
        try {
            $resp = Http::post("https://api.telegram.org/bot{$this->botToken}/deleteWebhook", [
                'drop_pending_updates' => true,
            ]);
            if ($resp->successful()) {
                Log::info('Telegram webhook deleted (drop_pending_updates=true).');

                return true;
            }
            Log::warning('Telegram deleteWebhook failed: '.$resp->body());

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function processMessage($update)
    {
        $text = data_get($update, 'message.text') ?? data_get($update, 'channel_post.text');
        if (! $text) {
            return;
        }

        $chatId = data_get($update, 'message.chat.id') ?? data_get($update, 'channel_post.chat.id');
        $rawLower = strtolower(trim($text));
        if ($rawLower === 'ping') {
            $this->sendMessage($chatId, 'pong');

            return;
        }

        // Handle commands
        if (str_starts_with($text, '/')) {
            $raw = trim($text);
            $tokens = preg_split('/\s+/', $raw);
            $command = strtolower($tokens[0]); // e.g. /cek_tiket_all or /cek
            if (str_contains($command, '@')) {
                $command = explode('@', $command)[0];
            }
            $args = array_slice($tokens, 1);
            $param = count($args) ? implode(' ', $args) : null;
            $cmdPlain = ltrim($command, '/');
            $cmdNormalized = str_replace('_', '', $cmdPlain);

            // Support space-based commands like "/cek tiket all"
            if ($command === '/cek' && count($args) >= 1) {
                $topic = strtolower($args[0]); // tiket|modem
                $second = $args[1] ?? null;
                if (in_array($topic, ['tiket', 'ticket'])) {
                    if ($second && strtolower($second) === 'all') {
                        $this->handleTicketAllCommand($chatId);
                    } else {
                        // If next token exists and not 'all', treat as ticket number
                        $ticketNumber = $second ?? null;
                        $this->handleTicketCommand($chatId, $ticketNumber);
                    }

                    return;
                } elseif ($topic === 'modem') {
                    if ($second && strtolower($second) === 'all') {
                        $this->handleModemAllCommand($chatId);
                    } else {
                        $lookup = $second ?? null;
                        $this->handleModemCommand($chatId, $lookup);
                    }

                    return;
                } else {
                    $this->sendMessage($chatId, "❓ Perintah tidak dikenali.\nKetik /bantuan untuk melihat daftar perintah.");

                    return;
                }
            }

            if (in_array($cmdNormalized, ['cektiketall', 'tiketall', 'ticketall'])) {
                $this->handleTicketAllCommand($chatId);

                return;
            }
            if (in_array($cmdNormalized, ['cekmodemall', 'modemall'])) {
                $this->handleModemAllCommand($chatId);

                return;
            }
            if (in_array($cmdNormalized, ['rekapmodem', 'statusmodemall', 'onlinemodem'])) {
                $this->handleModemAllCommand($chatId);

                return;
            }
            if (in_array($cmdNormalized, ['cektiket', 'tiket', 'ticket'])) {
                $this->handleTicketCommand($chatId, $param);

                return;
            }
            if (in_array($cmdNormalized, ['cekmodem', 'modem'])) {
                $this->handleModemCommand($chatId, $param);

                return;
            }

            switch ($command) {
                case '/status_ticket':
                case '/ticket':
                case '/cek_tiket':
                    $this->handleTicketCommand($chatId, $param);
                    break;
                case '/cek_tiket_all':
                case '/tiket_all':
                case '/ticket_all':
                    $this->handleTicketAllCommand($chatId);
                    break;
                case '/status_modem':
                case '/modem':
                case '/cek_modem':
                    $this->handleModemCommand($chatId, $param);
                    break;
                case '/cek_modem_all':
                case '/modem_all':
                case '/rekap_modem':
                case '/status_modem_all':
                    $this->handleModemAllCommand($chatId);
                    break;
                case '/help':
                case '/bantuan':
                    $this->sendMessage($chatId, "🤖 *Bantuan Bot MStore*\n\n/cek_tiket [No. Tiket] atau /cektiket [No]\n/cek_modem [ID/SN] atau /cekmodem [ID/SN]\n/cek_tiket_all atau /cektiketall\n/cek_modem_all atau /cekmodemall\n/rekap_modem\n\nJuga bisa: /cek tiket all, /cek modem all, rekap modem");
                    break;
                default:
                    $this->sendMessage($chatId, "❓ Perintah tidak dikenali.\nKetik /bantuan untuk melihat daftar perintah.");
                    break;
            }
        } else {
            // Plain text handlers (tanpa slash)
            $plain = preg_split('/\s+/', $rawLower);
            if (count($plain) >= 2 && $plain[0] === 'cek') {
                $topic = $plain[1]; // tiket|modem
                $third = $plain[2] ?? null;
                if ($topic === 'tiket' || $topic === 'ticket') {
                    if ($third === 'all') {
                        $this->handleTicketAllCommand($chatId);

                        return;
                    }
                    $ticketNumber = $third ?? null;
                    $this->handleTicketCommand($chatId, $ticketNumber);

                    return;
                }
                if ($topic === 'modem') {
                    if ($third === 'all') {
                        $this->handleModemAllCommand($chatId);

                        return;
                    }
                    $lookup = $third ?? null;
                    $this->handleModemCommand($chatId, $lookup);

                    return;
                }
                if ($topic === 'rekap' && $third === 'modem') {
                    $this->handleModemAllCommand($chatId);

                    return;
                }
                $this->sendMessage($chatId, "❓ Perintah tidak dikenali.\nKetik /bantuan untuk melihat daftar perintah.");

                return;
            }
        }
    }

    protected function handleTicketCommand($chatId, $ticketNumber)
    {
        if (empty($ticketNumber)) {
            $this->sendMessage($chatId, "⚠️ Harap masukkan nomor tiket.\nContoh: `/cek_tiket TKT-20240101-1234`");

            return;
        }

        $ticket = \App\Models\Ticket::where('ticket_number', $ticketNumber)->first();

        if (! $ticket) {
            $this->sendMessage($chatId, "❌ Tiket dengan nomor `{$ticketNumber}` tidak ditemukan.");

            return;
        }

        $statusEmoji = match ($ticket->status) {
            'open' => '🔴',
            'assigned' => '🟡',
            'in_progress' => '🟠',
            'solved' => '🟢',
            'closed' => '⚫',
            default => '⚪'
        };

        $message = "🎫 *Status Tiket*\n\n";
        $message .= "*Nomor:* `".self::escape($ticket->ticket_number)."`\n";
        $message .= "*Subjek:* ".self::escape($ticket->subject)."\n";
        $message .= '*Pelanggan:* '.self::escape($ticket->customer->name ?? '-')."\n";
        $message .= "*Status:* {$statusEmoji} ".ucfirst(self::escape($ticket->status))."\n";
        $message .= '*Teknisi:* '.self::escape($ticket->technicians->pluck('name')->join(', ') ?: '-')."\n";
        $message .= '*Koordinator:* '.self::escape($ticket->coordinator->name ?? '-')."\n";
        $message .= '*Update Terakhir:* '.$ticket->updated_at->format('d M Y H:i');

        $this->sendMessage($chatId, $message);
    }

    protected function handleModemCommand($chatId, $search)
    {
        if (empty($search)) {
            $this->sendMessage($chatId, "⚠️ Harap masukkan ID Pelanggan atau Serial Number.\nContoh: `/cek_modem 123`");

            return;
        }

        // Try to find customer by ID first, then ONU Serial
        $customer = \App\Models\Customer::where('id', $search)
            ->orWhere('onu_serial', $search)
            ->first();

        if (! $customer) {
            $this->sendMessage($chatId, "❌ Pelanggan dengan ID/Serial `{$search}` tidak ditemukan.");

            return;
        }

        if (empty($customer->onu_serial)) {
            $this->sendMessage($chatId, "⚠️ Pelanggan *{$customer->name}* tidak memiliki Serial Number ONU yang terdaftar.");

            return;
        }

        $this->sendMessage($chatId, "🔍 Memeriksa status modem untuk *{$customer->name}*...");

        try {
            $genieService = app(\App\Services\GenieACSService::class);
            $status = $genieService->getDeviceStatus($customer->onu_serial);

            if (isset($status['error'])) {
                $this->sendMessage($chatId, '⚠️ Gagal mengambil status dari GenieACS: '.$status['error']);

                return;
            }

            $isOnline = $status['online'] ?? false;
            $lastInform = $status['last_inform'] ?? 'Never';

            // Format Last Inform
            if ($lastInform !== 'Never') {
                $lastInform = \Carbon\Carbon::parse($lastInform)->setTimezone('Asia/Jakarta')->format('d M Y H:i:s');
            }

            $emoji = $isOnline ? '🟢' : '🔴';
            $statusText = $isOnline ? 'ONLINE' : 'OFFLINE';

            $response = "📡 *Status Modem*\n\n";
            $response .= "*Pelanggan:* ".self::escape($customer->name)."\n";
            $response .= "*SN:* `".self::escape($customer->onu_serial)."`\n";
            $response .= "*Status:* {$emoji} *{$statusText}*\n";
            $response .= "*Terakhir Terlihat:* ".self::escape($lastInform);

            $this->sendMessage($chatId, $response);

        } catch (\Exception $e) {
            Log::error('Telegram Modem Check Error: '.$e->getMessage());
            $this->sendMessage($chatId, '❌ Terjadi kesalahan saat memeriksa status modem.');
        }
    }

    protected function handleTicketAllCommand($chatId)
    {
        $statuses = ['open', 'assigned', 'in_progress', 'pending', 'solved', 'closed'];
        $counts = [];
        foreach ($statuses as $s) {
            $counts[$s] = \App\Models\Ticket::where('status', $s)->count();
        }

        $activeTickets = \App\Models\Ticket::with('customer')
            ->whereIn('status', ['open', 'assigned', 'in_progress', 'pending'])
            ->latest()
            ->limit(20)
            ->get();

        $mapEmoji = function ($status) {
            return match ($status) {
                'open' => '🔴',
                'assigned' => '🟡',
                'in_progress' => '🟠',
                'pending' => '🟤',
                'solved' => '🟢',
                'closed' => '⚫',
                default => '⚪'
            };
        };

        $msg = "🎫 *Rekap Semua Tiket*\n\n";
        $msg .= '*Total:* '.array_sum($counts)."\n";
        $msg .= "Open: {$counts['open']}\n";
        $msg .= "Assigned: {$counts['assigned']}\n";
        $msg .= "In Progress: {$counts['in_progress']}\n";
        $msg .= "Pending: {$counts['pending']}\n";
        $msg .= "Solved: {$counts['solved']}\n";
        $msg .= "Closed: {$counts['closed']}\n\n";
        $msg .= "*20 Tiket Aktif Terbaru:*\n";

        foreach ($activeTickets as $t) {
            $emoji = $mapEmoji($t->status);
            $cust = self::escape($t->customer->name ?? '-');
            $ticketNum = self::escape($t->ticket_number);
            $subject = self::escape($t->subject);
            $msg .= "- {$emoji} `{$ticketNum}` | {$subject} | {$cust}\n";
        }

        $this->sendMessage($chatId, $msg);
    }

    protected function handleModemAllCommand($chatId)
    {
        $customers = \App\Models\Customer::query()
            ->where('status', 'active')
            ->whereNotNull('onu_serial')
            ->get(['id', 'name', 'onu_serial']);
        $customerIds = $customers->pluck('id');
        $freshCutoff = \Carbon\Carbon::now()->subMinutes(15);
        $detailLimit = (int) Setting::getValue('telegram_monitor_detail_list_limit', '20');
        if ($detailLimit < 5) {
            $detailLimit = 5;
        }
        if ($detailLimit > 100) {
            $detailLimit = 100;
        }

        $online = \App\Models\GenieDeviceStatus::query()
            ->whereIn('customer_id', $customerIds)
            ->where('is_online', true)
            ->where('updated_at', '>=', $freshCutoff)
            ->count();
        $offlineDirect = \App\Models\GenieDeviceStatus::query()
            ->whereIn('customer_id', $customerIds)
            ->where('is_online', false)
            ->count();
        $offlineStaleOnline = \App\Models\GenieDeviceStatus::query()
            ->whereIn('customer_id', $customerIds)
            ->where('is_online', true)
            ->where('updated_at', '<', $freshCutoff)
            ->count();
        $offline = $offlineDirect + $offlineStaleOnline;
        $unknown = max(0, $customers->count() - $online - $offline);

        $onlineStatuses = \App\Models\GenieDeviceStatus::query()
            ->with('customer:id,name,pppoe_user')
            ->whereIn('customer_id', $customerIds)
            ->where('is_online', true)
            ->where('updated_at', '>=', $freshCutoff)
            ->latest('updated_at')
            ->limit($detailLimit)
            ->get();
        $offlineStatuses = \App\Models\GenieDeviceStatus::query()
            ->with('customer:id,name,pppoe_user')
            ->whereIn('customer_id', $customerIds)
            ->where(function ($q) use ($freshCutoff) {
                $q->where('is_online', false)
                    ->orWhere(function ($q2) use ($freshCutoff) {
                        $q2->where('is_online', true)
                            ->where('updated_at', '<', $freshCutoff);
                    });
            })
            ->latest('updated_at')
            ->limit($detailLimit)
            ->get();

        $msg = "📡 *Rekap Status Modem Pelanggan (Detail Mode)*\n\n";
        $msg .= 'Total Pelanggan: '.$customers->count()."\n";
        $msg .= "MODE ONLINE: 🟢 {$online}\n";
        $msg .= "MODE OFFLINE: 🔴 {$offline}\n";
        $msg .= "BELUM SINKRON: ⚪ {$unknown}\n\n";
        $msg .= "*{$detailLimit} ONLINE Terbaru:*\n";
        if ($onlineStatuses->isEmpty()) {
            $msg .= "- Tidak ada data online.\n";
        } else {
            foreach ($onlineStatuses as $status) {
                $customerName = self::escape($status->customer?->name ?? '-');
                $pppoe = self::escape($status->customer?->pppoe_user ?: '-');
                $ip = self::escape($status->tr069_ip ?: '-');
                $msg .= "- 🟢 {$customerName} | `{$pppoe}` | IP: `{$ip}`\n";
            }
        }

        $msg .= "\n*{$detailLimit} OFFLINE Terbaru:*\n";
        if ($offlineStatuses->isEmpty()) {
            $msg .= "- Tidak ada data offline.\n";
        } else {
            foreach ($offlineStatuses as $status) {
                $customerName = self::escape($status->customer?->name ?? '-');
                $pppoe = self::escape($status->customer?->pppoe_user ?: '-');
                $ip = self::escape($status->tr069_ip ?: '-');
                $reason = self::escape($status->last_reason ?: '-');
                $msg .= "- 🔴 {$customerName} | `{$pppoe}` | IP: `{$ip}` | {$reason}\n";
            }
        }

        $this->sendMessage($chatId, $msg);
    }

    public function sendMessage($chatId, $message)
    {
        if (empty($this->botToken)) {
            Log::warning('Telegram Bot Token is not set.');

            return false;
        }

        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ];
            $response = Http::timeout(8)->connectTimeout(3)->retry(2, 200)->post($this->apiUrl, $payload);

            if ($response->successful()) {
                return true;
            }

            $responseBody = (string) $response->body();
            $isParseError = str_contains(strtolower($responseBody), "can't parse entities");

            // Jika error karena format Markdown, coba kirim sebagai plain text tanpa log error yang menakutkan
            if ($isParseError) {
                Log::warning('Telegram Markdown parse failure, attempting plain text fallback.', [
                    'chat_id' => $chatId,
                    'error' => $responseBody,
                ]);

                $fallbackPayload = [
                    'chat_id' => $chatId,
                    'text' => $message,
                ];
                $fallbackResponse = Http::timeout(8)->connectTimeout(3)->retry(1, 200)->post($this->apiUrl, $fallbackPayload);
                
                if ($fallbackResponse->successful()) {
                    return true;
                }

                Log::error('Telegram plain text fallback failed: '.$fallbackResponse->body(), [
                    'chat_id' => $chatId,
                ]);
            } else {
                Log::error('Telegram API Error: '.$responseBody, [
                    'chat_id' => $chatId,
                    'parse_mode' => 'Markdown',
                ]);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Telegram Service Error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Escape special characters for Telegram Markdown (legacy).
     *
     * @param string|null $text
     * @return string
     */
    public static function escape($text)
    {
        if ($text === null) {
            return '';
        }

        // Characters to escape in legacy Markdown: _, *, `, [
        return str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $text);
    }

    public function sendToTechnicianGroup($message)
    {
        return $this->sendGroupNotification($message, 'ticket');
    }

    /**
     * Send System Notification to Group (Attendance/Ticket)
     */
    public function sendGroupNotification(string $message, string $category = 'ticket')
    {
        $enabledKey = "telegram_{$category}_notification_enabled";
        $groupIdKey = "telegram_{$category}_group_id";

        $isEnabled = Setting::getValue($enabledKey, '1') == '1';
        $target = Setting::getValue($groupIdKey, Setting::getValue('telegram_technician_group_chat_id'));

        if (! $isEnabled) {
            return false;
        }

        if (empty($target)) {
            Log::warning("Telegram Group Notification ID for {$category} not set.");
            return false;
        }

        return $this->sendMessage($target, $message);
    }

    /**
     * Send Telegram notification for ticket events.
     */
    public function sendTicketNotification(\App\Models\Ticket $ticket, string $type = 'created', ?string $customDescription = null): void
    {
        try {
            $customerName = $ticket->customer ? $ticket->customer->name : 'N/A';
            $locationLink = $ticket->location ? 'https://maps.google.com/?q='.urlencode($ticket->location) : '#';

            $settingKey = $type === 'solved' ? 'telegram_ticket_solved_template' : 'telegram_ticket_template';
            $templateSetting = Setting::where('key', $settingKey)->first();
            $template = $templateSetting ? $templateSetting->value : null;

            $technicianNames = $ticket->technicians->pluck('name')->join(', ');
            if (empty($technicianNames)) {
                $technicianNames = '-';
            }

            $coordinatorName = $ticket->coordinator ? $ticket->coordinator->name : '-';

            if (empty($template)) {
                if ($type === 'solved') {
                    $template = "✅ *TIKET SELESAI (TICKET SOLVED)*\n\n".
                               "🆔 *No:* `{ticket_number}`\n".
                               "📝 *Subject:* `{subject}`\n".
                               "👤 *Customer:* `{customer_name}`\n".
                               "👷 *Teknisi:* `{technicians}`\n".
                               "👔 *Koordinator:* `{coordinator}`\n".
                               "📍 *Lokasi:* `{location}`\n".
                               "⚠️ *Prioritas:* `{priority}`\n".
                               "📄 *Keterangan Selesai:* `{description}`\n\n".
                               "Tiket telah diselesaikan oleh teknisi.\n".
                               '[Lihat Lokasi]({location_link})';
                } else {
                    $template = "🔔 *TIKET BARU (NEW TICKET)*\n\n".
                               "🆔 *No:* `{ticket_number}`\n".
                               "📝 *Subject:* `{subject}`\n".
                               "👤 *Customer:* `{customer_name}`\n".
                               "👷 *Teknisi:* `{technicians}`\n".
                               "👔 *Koordinator:* `{coordinator}`\n".
                               "📍 *Lokasi:* `{location}`\n".
                               "⚠️ *Prioritas:* `{priority}`\n".
                               "📄 *Deskripsi:* `{description}`\n\n".
                               "Silakan cek aplikasi untuk detail lebih lanjut.\n".
                               '[Lihat Lokasi]({location_link})';
                }
            }

            $description = $customDescription ?? $ticket->description;

            $replacements = [
                '{ticket_number}' => "`".self::escape($ticket->ticket_number)."`",
                '{subject}' => self::escape($ticket->subject),
                '{customer_name}' => self::escape($customerName),
                '{technicians}' => self::escape($technicianNames),
                '{coordinator}' => self::escape($coordinatorName),
                '{location}' => self::escape($ticket->location ?? '-'),
                '{priority}' => self::escape(ucfirst($ticket->priority)),
                '{description}' => self::escape($description ?? '-'),
                '{location_link}' => $locationLink,
            ];

            $message = str_replace(array_keys($replacements), array_values($replacements), $template);
            $this->sendToTechnicianGroup($message);
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram {$type} notification: ".$e->getMessage());
        }
    }
}
