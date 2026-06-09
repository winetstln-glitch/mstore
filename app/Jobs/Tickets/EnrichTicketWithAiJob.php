<?php

namespace App\Jobs\Tickets;

use App\Services\TicketAiEnrichmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnrichTicketWithAiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $ticketId,
    ) {
        $this->onQueue('ai');
    }

    public function handle(TicketAiEnrichmentService $service): void
    {
        $service->enrichTicket($this->ticketId);
    }
}

