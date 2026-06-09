<?php

namespace App\Repositories\Eloquent;

use App\Models\SlaRule;
use App\Repositories\Contracts\SlaRuleRepositoryInterface;
use Illuminate\Support\Collection;

class SlaRuleRepository implements SlaRuleRepositoryInterface
{
    public function activeOrdered(): Collection
    {
        return SlaRule::query()
            ->where('is_active', true)
            ->orderBy('threshold_minutes')
            ->get();
    }

    public function findByStatus(string $status): ?SlaRule
    {
        return SlaRule::query()
            ->where('is_active', true)
            ->where('status', $status)
            ->orderBy('threshold_minutes')
            ->first();
    }
}

