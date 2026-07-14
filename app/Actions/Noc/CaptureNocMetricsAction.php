<?php

namespace App\Actions\Noc;

use App\Services\NocMetricsService;

class CaptureNocMetricsAction
{
    public function __construct(
        private readonly NocMetricsService $metrics,
    ) {}

    public function execute(): array
    {
        return $this->metrics->capture();
    }
}

