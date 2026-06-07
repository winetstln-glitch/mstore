<?php

namespace App\Actions\WhatsApp;

use App\Jobs\WhatsApp\SendWhatsAppMessageJob;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessMultiFormatMessageAction
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService
    ) {}

    public function execute(string $phoneNumber, array $replyData): void
    {
        $type = $replyData['type'] ?? 'text';
        $text = $replyData['text'] ?? '';
        $filePath = $replyData['file_path'] ?? null;
        $fileType = $replyData['file_type'] ?? null;
        $mediaUrl = $replyData['media_url'] ?? null;

        switch ($type) {
            case 'text':
                $this->sendTextMessage($phoneNumber, $text);
                break;
            case 'image':
                $this->sendImageMessage($phoneNumber, $text, $filePath, $mediaUrl, $fileType);
                break;
            case 'document':
                $this->sendDocumentMessage($phoneNumber, $text, $filePath, $mediaUrl, $fileType);
                break;
            case 'button':
                $this->sendButtonMessage($phoneNumber, $text, $replyData['buttons'] ?? []);
                break;
            case 'list':
                $this->sendListMessage($phoneNumber, $text, $replyData['list'] ?? []);
                break;
            default:
                $this->sendTextMessage($phoneNumber, $text);
        }
    }

    private function sendTextMessage(string $phoneNumber, string $text): void
    {
        SendWhatsAppMessageJob::dispatch($phoneNumber, $text);
    }

    private function sendImageMessage(string $phoneNumber, string $text, ?string $filePath, ?string $mediaUrl, ?string $fileType): void
    {
        $url = $mediaUrl ?? $this->getFileUrl($filePath);

        if (empty($url)) {
            $this->sendTextMessage($phoneNumber, $text);
            return;
        }

        SendWhatsAppMessageJob::dispatch($phoneNumber, $text, $url, 'image');
    }

    private function sendDocumentMessage(string $phoneNumber, string $text, ?string $filePath, ?string $mediaUrl, ?string $fileType): void
    {
        $url = $mediaUrl ?? $this->getFileUrl($filePath);

        if (empty($url)) {
            $this->sendTextMessage($phoneNumber, $text);
            return;
        }

        SendWhatsAppMessageJob::dispatch($phoneNumber, $text, $url, 'document');
    }

    private function sendButtonMessage(string $phoneNumber, string $text, array $buttons): void
    {
        // For now, format buttons as text, since not all providers support button messages
        if (!empty($buttons)) {
            $text .= "\n\n";
            foreach ($buttons as $btn) {
                $text .= (is_array($btn) ? ($btn['text'] ?? $btn['id'] ?? '') : $btn) . "\n";
            }
        }

        $this->sendTextMessage($phoneNumber, $text);
    }

    private function sendListMessage(string $phoneNumber, string $text, array $list): void
    {
        // For now, format list as text, since not all providers support list messages
        if (!empty($list)) {
            $text .= "\n\n";
            foreach ($list as $idx => $item) {
                $text .= ($idx + 1) . ". " . (is_array($item) ? ($item['title'] ?? $item['text'] ?? '') : $item) . "\n";
            }
        }

        $this->sendTextMessage($phoneNumber, $text);
    }

    private function getFileUrl(?string $filePath): ?string
    {
        if (empty($filePath)) {
            return null;
        }

        if (str_starts_with($filePath, 'http')) {
            return $filePath;
        }

        return Storage::disk('public')->url($filePath);
    }
}
