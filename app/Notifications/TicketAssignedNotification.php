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
        return self::buildMessage($this->ticket, $notifiable, 'whatsapp');
    }

    public function toTelegram(object $notifiable)
    {
        return self::buildMessage($this->ticket, $notifiable, 'telegram');
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

    public static function defaultWhatsAppTemplate(): string
    {
        return "*TUGAS BARU (TICKET ASSIGNED)*\n\n".
            "Halo {technician_name},\n".
            "Anda telah ditugaskan untuk tiket berikut:\n\n".
            "🎫 *No Tiket:* {ticket_number}\n".
            "📝 *Subject:* {ticket_subject}\n".
            "👤 *Customer:* {customer_name}\n".
            "📍 *Lokasi:* {ticket_location}\n".
            "⚠️ *Prioritas:* {ticket_priority}\n".
            "📄 *Deskripsi:* {ticket_description}\n\n".
            "Segera proses tiket ini melalui link berikut:\n{ticket_url}";
    }

    public static function defaultTelegramTemplate(): string
    {
        return "<b>TUGAS BARU (TICKET ASSIGNED)</b>\n\n".
            "Halo {technician_name},\n".
            "Anda telah ditugaskan untuk tiket berikut:\n\n".
            "🎫 <b>No Tiket:</b> {ticket_number}\n".
            "📝 <b>Subject:</b> {ticket_subject}\n".
            "👤 <b>Customer:</b> {customer_name}\n".
            "📍 <b>Lokasi:</b> {ticket_location}\n".
            "⚠️ <b>Prioritas:</b> {ticket_priority}\n".
            "📄 <b>Deskripsi:</b> {ticket_description}\n\n".
            "Segera proses tiket ini melalui link berikut:\n{ticket_url}";
    }

    public static function defaultTemplate(): string
    {
        return self::defaultWhatsAppTemplate();
    }

    public static function buildMessage(Ticket $ticket, object $notifiable, string $channel = 'whatsapp'): string
    {
        if ($channel === 'whatsapp') {
            $template = (string) Setting::getValue('ticket_assigned_whatsapp_template', self::defaultWhatsAppTemplate());
        } else {
            $template = (string) Setting::getValue('ticket_assigned_telegram_template', self::defaultTelegramTemplate());
        }

        $technicianName = $notifiable->name ?? 'Teknisi';
        $ticketNumber = $ticket->ticket_number;
        $ticketSubject = $ticket->subject;
        $customerName = $ticket->customer->name ?? 'Unknown';
        $ticketLocation = $ticket->location ?? '-';
        $ticketPriority = ucfirst((string) $ticket->priority);
        $ticketDescription = $ticket->description ?: '-';
        $ticketUrl = route('tickets.show', $ticket->id);

        if ($channel === 'telegram') {
            $technicianName = \App\Services\TelegramService::escape($technicianName);
            $ticketNumber = \App\Services\TelegramService::escape($ticketNumber);
            $ticketSubject = \App\Services\TelegramService::escape($ticketSubject);
            $customerName = \App\Services\TelegramService::escape($customerName);
            $ticketLocation = \App\Services\TelegramService::escape($ticketLocation);
            $ticketPriority = \App\Services\TelegramService::escape($ticketPriority);
            $ticketDescription = \App\Services\TelegramService::escape($ticketDescription);
        }

        return str_replace(
            ['{technician_name}', '{ticket_number}', '{ticket_subject}', '{customer_name}', '{ticket_location}', '{ticket_priority}', '{ticket_description}', '{ticket_url}'],
            [
                $technicianName,
                $ticketNumber,
                $ticketSubject,
                $customerName,
                $ticketLocation,
                $ticketPriority,
                $ticketDescription,
                $ticketUrl,
            ],
            $template
        );
    }
}
