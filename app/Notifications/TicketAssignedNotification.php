<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Models\Ticket;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    public $ticket;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
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
        $url = route('tickets.show', $this->ticket->id);
        $customerName = $this->ticket->customer->name ?? 'Unknown';
        $location = $this->ticket->location ?? '-';

        // Fetch template from settings or use default
        $template = Setting::where('key', 'whatsapp_ticket_template')->value('value');

        if (!$template) {
            return "*TUGAS BARU (TICKET ASSIGNED)*\n\n" .
                   "Halo {$notifiable->name},\n" .
                   "Anda telah ditugaskan untuk tiket berikut:\n\n" .
                   "🎫 *No Tiket:* {$this->ticket->ticket_number}\n" .
                   "📝 *Subject:* {$this->ticket->subject}\n" .
                   "👤 *Customer:* {$customerName}\n" .
                   "📍 *Lokasi:* {$location}\n\n" .
                   "Segera proses tiket ini melalui link berikut:\n{$url}";
        }

        // Replace placeholders
        return str_replace(
            ['{technician_name}', '{ticket_number}', '{subject}', '{customer_name}', '{location}', '{url}'],
            [$notifiable->name, $this->ticket->ticket_number, $this->ticket->subject, $customerName, $location, $url],
            $template
        );
    }

    public function toTelegram(object $notifiable)
    {
        return $this->toWhatsApp($notifiable);
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
}
