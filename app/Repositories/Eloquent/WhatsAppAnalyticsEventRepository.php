<?php

namespace App\Repositories\Eloquent;

use App\Models\WhatsAppAnalyticsEvent;
use App\Repositories\Contracts\WhatsAppAnalyticsEventRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class WhatsAppAnalyticsEventRepository implements WhatsAppAnalyticsEventRepositoryInterface
{
    public function create(array $attributes): WhatsAppAnalyticsEvent
    {
        return WhatsAppAnalyticsEvent::create($attributes);
    }

    public function countBetween(CarbonInterface $from, CarbonInterface $to, array $filters = []): int
    {
        return WhatsAppAnalyticsEvent::query()
            ->whereBetween('occurred_at', [$from, $to])
            ->when(isset($filters['direction']), fn ($q) => $q->where('direction', $filters['direction']))
            ->when(isset($filters['used_ai']), fn ($q) => $q->where('used_ai', (bool) $filters['used_ai']))
            ->when(isset($filters['is_fallback']), fn ($q) => $q->where('is_fallback', (bool) $filters['is_fallback']))
            ->count();
    }

    public function topIntents(CarbonInterface $from, CarbonInterface $to, int $limit = 10): Collection
    {
        return WhatsAppAnalyticsEvent::query()
            ->selectRaw('intent, COUNT(*) as total')
            ->whereBetween('occurred_at', [$from, $to])
            ->whereNotNull('intent')
            ->groupBy('intent')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }
}

