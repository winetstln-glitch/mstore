<?php

namespace App\Jobs\Noc;

use App\Actions\Noc\CaptureNocMetricsAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CaptureNocMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Jumlah maksimum percobaan ulang jika gagal */
    public int $tries = 3;

    /** Timeout eksekusi dalam detik */
    public int $timeout = 60;

    public function __construct()
    {
        $this->onQueue('monitoring');
    }

    public function handle(CaptureNocMetricsAction $action): void
    {
        $action->execute();
    }

    /**
     * Dipanggil ketika job gagal setelah semua percobaan ulang habis.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical('CaptureNocMetricsJob gagal permanent setelah '.$this->tries.' percobaan.', [
            'exception' => $exception->getMessage(),
            'trace'     => $exception->getTraceAsString(),
        ]);
    }
}
