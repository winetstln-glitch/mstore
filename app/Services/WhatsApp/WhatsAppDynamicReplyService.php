<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMenu;
use App\Models\WhatsAppSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppDynamicReplyService
{
    public function __construct() {}

    public function getReply(string $message, ?User $user = null, ?WhatsAppSession $session = null): array
    {
        $message = strtolower(trim($message));

        if ($session && $session->current_node) {
            return $this->handleMultiTurnConversation($message, $user, $session);
        }

        $menu = $this->findMatchingMenu($message);

        if ($menu) {
            $menu->incrementHitCount();
            return $this->buildReplyFromMenu($menu, $user, $session);
        }

        return $this->handleFallback($message, $user);
    }

    private function findMatchingMenu(string $message): ?WhatsAppMenu
    {
        $exactMatch = WhatsAppMenu::active()
            ->where('keyword', $message)
            ->first();

        if ($exactMatch) {
            return $exactMatch;
        }

        $menus = WhatsAppMenu::active()
            ->where('enable_fuzzy_match', true)
            ->orderBy('priority', 'desc')
            ->get();

        $bestMatch = null;
        $highestScore = 0;

        foreach ($menus as $menu) {
            $score = $this->calculateMatchScore($message, $menu->keyword);
            if ($score > 0.6 && $score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $menu;
            }
        }

        return $bestMatch;
    }

    private function calculateMatchScore(string $input, string $keyword): float
    {
        $inputWords = explode(' ', $input);
        $keywordWords = explode(' ', $keyword);

        $matches = 0;
        foreach ($keywordWords as $kw) {
            foreach ($inputWords as $iw) {
                similar_text(strtolower($kw), strtolower($iw), $percent);
                if ($percent > 70) {
                    $matches++;
                    break;
                }
            }
        }

        return $matches / max(count($keywordWords), 1);
    }

    private function buildReplyFromMenu(WhatsAppMenu $menu, ?User $user = null, ?WhatsAppSession $session = null): array
    {
        $responseText = $this->replaceVariables($menu->response_text, $user, $session);

        return [
            'type' => $menu->type,
            'text' => $responseText,
            'file_path' => $menu->file_path,
            'file_type' => $menu->file_type,
            'menu' => $menu,
        ];
    }

    private function replaceVariables(string $text, ?User $user = null, ?WhatsAppSession $session = null): string
    {
        $variables = [
            '{nama_user}' => $user?->name ?? 'Teman',
            '{jam_sekarang}' => Carbon::now()->format('H:i'),
            '{tanggal_sekarang}' => Carbon::now()->translatedFormat('l, d F Y'),
            '{tahun}' => Carbon::now()->year,
            '{bulan}' => Carbon::now()->translatedFormat('F'),
        ];

        if ($session && $session->payload) {
            foreach ($session->payload as $key => $value) {
                $variables['{' . $key . '}'] = $value;
            }
        }

        return str_replace(array_keys($variables), array_values($variables), $text);
    }

    private function handleMultiTurnConversation(string $message, ?User $user, WhatsAppSession $session): array
    {
        $node = $session->current_node;

        switch ($node) {
            case 'request_ticket':
                return $this->handleTicketRequestStep($message, $user, $session);
            case 'request_attendance':
                return $this->handleAttendanceRequestStep($message, $user, $session);
            default:
                $session->reset();
                return $this->handleFallback($message, $user);
        }
    }

    private function handleTicketRequestStep(string $message, ?User $user, WhatsAppSession $session): array
    {
        $step = $session->step;

        if ($step === 1) {
            $session->updatePayload(['ticket_title' => $message]);
            $session->setCurrentNode('request_ticket');

            return [
                'type' => 'text',
                'text' => "Terima kasih! Sekarang silakan jelaskan detail kendala yang Anda alami:",
            ];
        }

        if ($step === 2) {
            $session->updatePayload(['ticket_description' => $message]);
            $payload = $session->payload;

            $session->reset();

            return [
                'type' => 'text',
                'text' => "Terima kasih! Tiket Anda telah dibuat:\n\n" .
                        "Judul: {$payload['ticket_title']}\n" .
                        "Deskripsi: {$payload['ticket_description']}\n\n" .
                        "Tim kami akan segera menindaklanjuti!",
            ];
        }

        $session->reset();
        return $this->handleFallback($message, $user);
    }

    private function handleAttendanceRequestStep(string $message, ?User $user, WhatsAppSession $session): array
    {
        $session->reset();
        return [
            'type' => 'text',
            'text' => "Untuk melakukan absensi, silakan kunjungi aplikasi MStore atau gunakan fitur absensi di website!",
        ];
    }

    private function handleFallback(string $message, ?User $user): array
    {
        Log::info('WhatsApp fallback triggered', [
            'message' => $message,
            'user_id' => $user?->id,
        ]);

        $useAIFallback = config('services.whatsapp.ai_fallback_enabled', false);

        if ($useAIFallback) {
            return $this->callAIAssistant($message, $user);
        }

        return [
            'type' => 'text',
            'text' => "Maaf, saya tidak memahami pesan Anda.\n\n" .
                    "Silakan ketik *bantuan* untuk melihat menu yang tersedia, " .
                    "atau hubungi tim support kami untuk bantuan lebih lanjut.",
        ];
    }

    private function callAIAssistant(string $message, ?User $user): array
    {
        return [
            'type' => 'text',
            'text' => "Saya sedang memproses permintaan Anda dengan AI Assistant...\n\n" .
                    "Untuk saat ini, silakan gunakan perintah yang tersedia di menu *bantuan*.",
        ];
    }
}
