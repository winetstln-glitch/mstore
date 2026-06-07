<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTicketAssignedNotification implements ShouldQueue
{
    public function handle(TicketAssigned $event)
    {
        $ticket = $event->ticket;
        $technician = $event->technician;

        Log::info("TicketAssignedListener: Ticket {$ticket->id} assigned to technician {$technician->id}");

        // TODO: Send WhatsApp/email notification to technician
        // TODO: Send WhatsApp notification to customer
    }
}
