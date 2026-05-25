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

        switch ($type) {
            case 'text':
                $this->sendTextMessage($phoneNumber, $text);
                break;
            case 'image':
                $this->sendImageMessage($phoneNumber, $text, $filePath, $fileType);
                break;
            case 'document':
                $this->sendDocumentMessage($phoneNumber, $text, $filePath, $fileType);
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

    private function sendImageMessage(string $phoneNumber, string $text, ?string $filePath, ?string $fileType): void
    {
        if (empty($filePath)) {
            $this->sendTextMessage($phoneNumber, $text);
            return;
        }

        $fullUrl = $this->getFileUrl($filePath);

        SendWhatsAppMessageJob::dispatch($phoneNumber, $text, $fullUrl, 'image');
    }

    private function sendDocumentMessage(string $phoneNumber, string $text, ?string $filePath, ?string $fileType): void
    {
        if (empty($filePath)) {
            $this->sendTextMessage($phoneNumber, $text);
            return;
        }

        $fullUrl = $this->getFileUrl($filePath);

        SendWhatsAppMessageJob::dispatch($phoneNumber, $text, $fullUrl, 'document');
    }

    private function sendButtonMessage(string $phoneNumber, string $text, array $buttons): void
    {
        if (empty($buttons)) {
            $this->sendTextMessage($phoneNumber, $text);
            return;
        }

        $this->sendTextMessage($phoneNumber, $text);
    }

    private function sendListMessage(string $phoneNumber, string $text, array $list): void
    {
        if (empty($list)) {
            $this->sendTextMessage($phoneNumber, $text);
            return;
        }

        $this->sendTextMessage($phoneNumber, $text);
    }

    private function getFileUrl(string $filePath): string
    {
        if (str_starts_with($filePath, 'http')) {
            return $filePath;
        }

        return Storage::disk('public')->url($filePath);
    }
}
