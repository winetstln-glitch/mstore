<?php

namespace App\Jobs\Sla;

use App\Services\SlaMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class EvaluateSlaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Jumlah maksimum percobaan ulang jika gagal */
    public int $tries = 3;

    /** Timeout eksekusi dalam detik */
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('monitoring');
    }

    public function handle(SlaMonitoringService $service): void
    {
        $service->evaluateOpenTickets();
        DispatchSlaEscalationNotificationsJob::dispatch();
    }

    /**
     * Dipanggil ketika job gagal setelah semua percobaan ulang habis.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical('EvaluateSlaJob gagal permanent — evaluasi SLA mungkin tidak berjalan!', [
            'exception' => $exception->getMessage(),
            'trace'     => $exception->getTraceAsString(),
        ]);
    }
}
