<?php

namespace App\Models\Scopes;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();
        if ($user && $user->company_id) {
            $builder->where($model->getTable() . '.company_id', $user->company_id);
        }
    }
}