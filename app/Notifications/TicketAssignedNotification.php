<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Models\Ticket;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $ticket;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
        $this->afterCommit = true;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', WhatsAppChannel::class, TelegramChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toWhatsApp(object $notifiable)
    {
        return self::buildMessage($this->ticket, $notifiable);
    }

    public function toTelegram(object $notifiable)
    {
        return self::buildMessage($this->ticket, $notifiable, null, true);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'customer_name' => $this->ticket->customer->name ?? 'Unknown',
            'location' => $this->ticket->location,
            'message' => "New ticket assigned: {$this->ticket->ticket_number} - {$this->ticket->subject}",
            'url' => route('tickets.show', $this->ticket->id),
        ];
    }

    public static function defaultTemplate(): string
    {
        return "*TUGAS BARU (TICKET ASSIGNED)*\n\n".
            "Halo {technician_name},\n".
            "Anda telah ditugaskan untuk tiket berikut:\n\n".
            "🎫 *No Tiket:* {ticket_number}\n".
            "📝 *Subject:* {subject}\n".
            "👤 *Customer:* {customer_name}\n".
            "📍 *Lokasi:* {location}\n".
            "⚠️ *Prioritas:* {priority}\n".
            "📄 *Deskripsi:* {description}\n\n".
            "Segera proses tiket ini melalui link berikut:\n{url}";
    }

    public static function buildMessage(Ticket $ticket, object $notifiable, ?string $customTemplate = null, bool $escapeForTelegram = false): string
    {
        $template = trim((string) ($customTemplate ?? ''));
        if ($template === '') {
            $template = (string) Setting::getValue('whatsapp_ticket_template', self::defaultTemplate());
        }

        $technicianName = $notifiable->name ?? 'Teknisi';
        $ticketNumber = $ticket->ticket_number;
        $subject = $ticket->subject;
        $customerName = $ticket->customer->name ?? 'Unknown';
        $location = $ticket->location ?? '-';
        $priority = ucfirst((string) $ticket->priority);
        $description = $ticket->description ?: '-';
        $url = route('tickets.show', $ticket->id);

        if ($escapeForTelegram) {
            $technicianName = \App\Services\TelegramService::escape($technicianName);
            $ticketNumber = \App\Services\TelegramService::escape($ticketNumber);
            $subject = \App\Services\TelegramService::escape($subject);
            $customerName = \App\Services\TelegramService::escape($customerName);
            $location = \App\Services\TelegramService::escape($location);
            $priority = \App\Services\TelegramService::escape($priority);
            $description = \App\Services\TelegramService::escape($description);
            // URL doesn't usually need escaping for Markdown unless it has special chars in brackets, 
            // but Telegram usually handles raw URLs fine.
        }

        return str_replace(
            ['{technician_name}', '{ticket_number}', '{subject}', '{customer_name}', '{location}', '{priority}', '{description}', '{url}'],
            [
                $technicianName,
                $ticketNumber,
                $subject,
                $customerName,
                $location,
                $priority,
                $description,
                $url,
            ],
            $template
        );
    }
}
