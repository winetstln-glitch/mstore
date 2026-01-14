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
        if (empty($this->botToken)) return [];

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
                    Log::warning("Telegram getUpdates Conflict: Webhook is set. Deleting webhook...");
                    $this->deleteWebhook();
                    return [];
                }
                Log::error("Telegram API Error: " . $response->body());
            }
        } catch (\Exception $e) {
            // Long-polling timeouts (cURL error 28) are expected when no messages arrive
            $msg = $e->getMessage();
            if (str_contains($msg, 'cURL error 28')) {
                Log::notice("Telegram getUpdates timeout (no updates).");
            } else {
                Log::error("Telegram getUpdates Error: " . $msg);
            }
        }
        return [];
    }

    public function deleteWebhook()
    {
        if (empty($this->botToken)) return false;
        try {
             $resp = Http::post("https://api.telegram.org/bot{$this->botToken}/deleteWebhook", [
                 'drop_pending_updates' => true,
             ]);
             if ($resp->successful()) {
                 Log::info("Telegram webhook deleted (drop_pending_updates=true).");
                 return true;
             }
             Log::warning("Telegram deleteWebhook failed: " . $resp->body());
             return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function processMessage($update)
    {
        $text = data_get($update, 'message.text') ?? data_get($update, 'channel_post.text');
        if (!$text) return;
 
        $chatId = data_get($update, 'message.chat.id') ?? data_get($update, 'channel_post.chat.id');
        $rawLower = strtolower(trim($text));
        if ($rawLower === 'ping') {
            $this->sendMessage($chatId, "pong");
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

            if (in_array($cmdNormalized, ['cektiketall','tiketall','ticketall'])) {
                $this->handleTicketAllCommand($chatId);
                return;
            }
            if (in_array($cmdNormalized, ['cekmodemall','modemall'])) {
                $this->handleModemAllCommand($chatId);
                return;
            }
            if (in_array($cmdNormalized, ['cektiket','tiket','ticket'])) {
                $this->handleTicketCommand($chatId, $param);
                return;
            }
            if (in_array($cmdNormalized, ['cekmodem','modem'])) {
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
                    $this->handleModemAllCommand($chatId);
                    break;
                case '/help':
                case '/bantuan':
                    $this->sendMessage($chatId, "🤖 *Bantuan Bot MStore*\n\n/cek_tiket [No. Tiket] atau /cektiket [No]\n/cek_modem [ID/SN] atau /cekmodem [ID/SN]\n/cek_tiket_all atau /cektiketall\n/cek_modem_all atau /cekmodemall\n\nJuga bisa: /cek tiket all, /cek modem all");
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

        if (!$ticket) {
            $this->sendMessage($chatId, "❌ Tiket dengan nomor `{$ticketNumber}` tidak ditemukan.");
            return;
        }

        $statusEmoji = match($ticket->status) {
            'open' => '🔴',
            'assigned' => '🟡',
            'in_progress' => '🟠',
            'solved' => '🟢',
            'closed' => '⚫',
            default => '⚪'
        };

        $message = "🎫 *Status Tiket*\n\n";
        $message .= "*Nomor:* `{$ticket->ticket_number}`\n";
        $message .= "*Subjek:* {$ticket->subject}\n";
        $message .= "*Pelanggan:* " . ($ticket->customer->name ?? '-') . "\n";
        $message .= "*Status:* {$statusEmoji} " . ucfirst($ticket->status) . "\n";
        $message .= "*Teknisi:* " . ($ticket->technicians->pluck('name')->join(', ') ?: '-') . "\n";
        $message .= "*Koordinator:* " . ($ticket->coordinator->name ?? '-') . "\n";
        $message .= "*Update Terakhir:* " . $ticket->updated_at->format('d M Y H:i');

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

        if (!$customer) {
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
                 $this->sendMessage($chatId, "⚠️ Gagal mengambil status dari GenieACS: " . $status['error']);
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
            $response .= "*Pelanggan:* {$customer->name}\n";
            $response .= "*SN:* `{$customer->onu_serial}`\n";
            $response .= "*Status:* {$emoji} *{$statusText}*\n";
            $response .= "*Terakhir Terlihat:* {$lastInform}";

            $this->sendMessage($chatId, $response);

        } catch (\Exception $e) {
            Log::error("Telegram Modem Check Error: " . $e->getMessage());
             $this->sendMessage($chatId, "❌ Terjadi kesalahan saat memeriksa status modem.");
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
             return match($status) {
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
         $msg .= "*Total:* " . array_sum($counts) . "\n";
         $msg .= "Open: {$counts['open']}\n";
         $msg .= "Assigned: {$counts['assigned']}\n";
         $msg .= "In Progress: {$counts['in_progress']}\n";
         $msg .= "Pending: {$counts['pending']}\n";
         $msg .= "Solved: {$counts['solved']}\n";
         $msg .= "Closed: {$counts['closed']}\n\n";
         $msg .= "*20 Tiket Aktif Terbaru:*\n";
 
         foreach ($activeTickets as $t) {
             $emoji = $mapEmoji($t->status);
             $cust = $t->customer->name ?? '-';
             $msg .= "- {$emoji} `{$t->ticket_number}` | {$t->subject} | {$cust}\n";
         }
 
         $this->sendMessage($chatId, $msg);
     }
 
     protected function handleModemAllCommand($chatId)
     {
         $customers = \App\Models\Customer::where('status', 'active')
             ->whereNotNull('onu_serial')
             ->get(['id', 'name', 'onu_serial']);
 
         $genieService = app(\App\Services\GenieACSService::class);
         $devices = $genieService->getDevices(1000);
 
         $onlineSerials = [];
         foreach ($devices as $device) {
             $serial = $device['_deviceId']['_SerialNumber'] ?? null;
             $lastInform = $device['_lastInform'] ?? null;
             if ($serial && $lastInform) {
                 $diff = now()->diffInSeconds(\Carbon\Carbon::parse($lastInform));
                 if ($diff < 300) {
                     $onlineSerials[$serial] = true;
                 }
             }
         }
 
         $online = 0;
         $offline = 0;
         $offlineList = [];
 
         foreach ($customers as $c) {
             if (isset($onlineSerials[$c->onu_serial])) {
                 $online++;
             } else {
                 $offline++;
                 if (count($offlineList) < 20) {
                     $offlineList[] = $c;
                 }
             }
         }
 
         $msg = "📡 *Rekap Status Modem Pelanggan*\n\n";
         $msg .= "Total Pelanggan: " . $customers->count() . "\n";
         $msg .= "ONLINE: {$online}\n";
         $msg .= "OFFLINE: {$offline}\n\n";
         $msg .= "*20 OFFLINE (contoh):*\n";
         foreach ($offlineList as $c) {
             $msg .= "- 🔴 {$c->name} | `{$c->onu_serial}`\n";
         }
 
         $this->sendMessage($chatId, $msg);
     }

    public function sendMessage($chatId, $message)
    {
        if (empty($this->botToken)) {
            Log::warning("Telegram Bot Token is not set.");
            return false;
        }

        try {
            $response = Http::post($this->apiUrl, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);

            if ($response->successful()) {
                return true;
            } else {
                Log::error("Telegram API Error: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Telegram Service Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendToTechnicianGroup($message)
    {
        $setting = Setting::where('key', 'telegram_technician_group_chat_id')->first();
        $chatId = $setting ? $setting->value : null;

        if (empty($chatId)) {
            Log::warning("Telegram Technician Group Chat ID is not set.");
            return false;
        }

        return $this->sendMessage($chatId, $message);
    }
}
