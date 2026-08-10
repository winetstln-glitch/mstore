<?php

namespace App\Jobs\Tickets;

use App\Services\TicketAiEnrichmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnrichTicketWithAiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Jumlah maksimum percobaan ulang (AI bisa timeout, retry wajar) */
    public int $tries = 2;

    /** Timeout lebih lama karena AI request bisa lambat */
    public int $timeout = 180;

    public function __construct(
        public readonly int $ticketId,
    ) {
        $this->onQueue('ai');
    }

    public function handle(TicketAiEnrichmentService $service): void
    {
        $service->enrichTicket($this->ticketId);
    }

    /**
     * Dipanggil ketika job gagal setelah semua percobaan ulang habis.
     */
    public function failed(Throwable $exception): void
    {
        Log::warning('EnrichTicketWithAiJob gagal untuk ticket #'.$this->ticketId.'.', [
            'ticket_id' => $this->ticketId,
            'exception' => $exception->getMessage(),
        ]);
        // AI enrichment tidak kritikal — tidak perlu alert admin, cukup log warning.
    }
}
