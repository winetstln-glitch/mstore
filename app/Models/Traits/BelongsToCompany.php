<?php

namespace App\Models\Traits;

use App\Models\Scopes\CompanyScope;
use App\Models\Company;
use App\Models\CompanyBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope());
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }
}