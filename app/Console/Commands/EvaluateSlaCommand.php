<?php

namespace App\Console\Commands;

use App\Jobs\Sla\EvaluateSlaJob;
use Illuminate\Console\Command;

class EvaluateSlaCommand extends Command
{
    protected $signature = 'sla:evaluate {--queue : Jalankan melalui queue monitoring}';

    protected $description = 'Evaluasi SLA ticket dan buat escalation queue bila breach';

    public function handle(): int
    {
        if ((bool) $this->option('queue')) {
            EvaluateSlaJob::dispatch();
            $this->info('EvaluateSlaJob dikirim ke queue monitoring.');

            return self::SUCCESS;
        }

        EvaluateSlaJob::dispatchSync();
        $this->info('Evaluasi SLA selesai (sinkron).');

        return self::SUCCESS;
    }
}

