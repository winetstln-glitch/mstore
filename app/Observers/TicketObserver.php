<?php

namespace App\Observers;

use App\Jobs\Tickets\EnrichTicketWithAiJob;
use App\Models\Ticket;
use App\Services\SlaMonitoringService;

class TicketObserver
{
    public function __construct(
        private readonly SlaMonitoringService $sla,
    ) {}

    public function created(Ticket $ticket): void
    {
        if (! $ticket->sla_deadline) {
            $ticket->forceFill(['sla_deadline' => $ticket->created_at?->copy()->addHours(24)])->saveQuietly();
        }

        EnrichTicketWithAiJob::dispatch($ticket->id);
    }

    public function updated(Ticket $ticket): void
    {
        if ($ticket->wasChanged('status') && in_array($ticket->status, ['closed', 'solved'], true)) {
            $this->sla->closeTicketBreaches($ticket);
        }
    }
}

