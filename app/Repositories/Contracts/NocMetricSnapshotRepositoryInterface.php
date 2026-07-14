<?php

namespace App\Repositories\Contracts;

use App\Models\NocMetricSnapshot;
use Carbon\CarbonInterface;

interface NocMetricSnapshotRepositoryInterface
{
    public function latest(): ?NocMetricSnapshot;

    public function create(array $attributes): NocMetricSnapshot;

    public function latestSince(CarbonInterface $since): ?NocMetricSnapshot;
}

