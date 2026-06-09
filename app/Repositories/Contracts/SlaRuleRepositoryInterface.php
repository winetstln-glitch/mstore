<?php

namespace App\Repositories\Contracts;

use App\Models\SlaRule;
use Illuminate\Support\Collection;

interface SlaRuleRepositoryInterface
{
    public function activeOrdered(): Collection;

    public function findByStatus(string $status): ?SlaRule;
}

