<?php

namespace App\Repositories\Eloquent;

use App\Models\NocMetricSnapshot;
use App\Repositories\Contracts\NocMetricSnapshotRepositoryInterface;
use Carbon\CarbonInterface;

class NocMetricSnapshotRepository implements NocMetricSnapshotRepositoryInterface
{
    public function latest(): ?NocMetricSnapshot
    {
        return NocMetricSnapshot::query()->latest('captured_at')->first();
    }

    public function latestSince(CarbonInterface $since): ?NocMetricSnapshot
    {
        return NocMetricSnapshot::query()
            ->where('captured_at', '>=', $since)
            ->latest('captured_at')
            ->first();
    }

    public function create(array $attributes): NocMetricSnapshot
    {
        return NocMetricSnapshot::create($attributes);
    }
}

