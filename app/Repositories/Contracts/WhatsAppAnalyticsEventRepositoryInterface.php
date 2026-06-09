<?php

namespace App\Repositories\Contracts;

use App\Models\WhatsAppAnalyticsEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface WhatsAppAnalyticsEventRepositoryInterface
{
    public function create(array $attributes): WhatsAppAnalyticsEvent;

    public function countBetween(CarbonInterface $from, CarbonInterface $to, array $filters = []): int;

    public function topIntents(CarbonInterface $from, CarbonInterface $to, int $limit = 10): Collection;
}

