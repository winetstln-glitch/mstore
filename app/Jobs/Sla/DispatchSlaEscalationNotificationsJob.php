<?php

namespace App\Jobs\Sla;

use App\Models\EscalationNotification;
use App\Models\Ticket;
use App\Services\TelegramService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DispatchSlaEscalationNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('notifications');
    }

    public function handle(WhatsAppService $whatsAppService, TelegramService $telegramService): void
    {
        EscalationNotification::query()
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->each(function (EscalationNotification $notification) use ($whatsAppService, $telegramService) {
                $this->dispatchOne($notification, $whatsAppService, $telegramService);
            });
    }

    private function dispatchOne(EscalationNotification $notification, WhatsAppService $whatsAppService, TelegramService $telegramService): void
    {
        $ticket = Ticket::query()->find($notification->ticket_id);
        if (! $ticket) {
            $notification->update([
                'status' => 'failed',
                'error_message' => 'Ticket not found',
                'attempt' => $notification->attempt + 1,
            ]);
            return;
        }

        $message = $this->buildMessage($ticket, (array) $notification->payload);

        try {
            $ok = false;
            if ($notification->channel === 'whatsapp') {
                $result = $whatsAppService->sendMessage($notification->target, $message, 'sla_escalation');
                $ok = (bool) ($result['success'] ?? false);
            } elseif ($notification->channel === 'telegram') {
                $ok = $telegramService->sendMessage($notification->target, $message);
            } elseif ($notification->channel === 'email') {
                Mail::raw($message, function ($mail) use ($notification) {
                    $mail->to($notification->target)->subject('SLA Escalation Ticket');
                });
                $ok = true;
            }

            $notification->update([
                'status' => $ok ? 'sent' : 'failed',
                'sent_at' => $ok ? now() : null,
                'attempt' => $notification->attempt + 1,
                'error_message' => $ok ? null : 'Dispatch failed',
            ]);
        } catch (\Throwable $e) {
            Log::warning('SLA escalation notification failed', [
                'id' => $notification->id,
                'channel' => $notification->channel,
                'target' => $notification->target,
                'error' => $e->getMessage(),
            ]);
            $notification->update([
                'status' => 'failed',
                'attempt' => $notification->attempt + 1,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ]);
        }
    }

    private function buildMessage(Ticket $ticket, array $payload): string
    {
        $rule = $payload['sla_rule'] ?? [];
        $status = (string) ($rule['status'] ?? ($ticket->sla_status ?? '-'));
        $ruleName = (string) ($rule['name'] ?? '-');

        $lines = [
            '🚨 SLA Escalation Ticket',
            "Ticket: {$ticket->ticket_number}",
            "Status Ticket: {$ticket->status}",
            "SLA: {$ruleName} ({$status})",
            "Prioritas: {$ticket->priority}",
            "Subject: {$ticket->subject}",
        ];

        return implode("\n", $lines);
    }
}

