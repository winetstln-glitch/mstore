<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait ScopesByUserArea
{
    public function scopeForUserArea(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (static::isUserSuperAdmin($user)) {
            return $query;
        }

        $table = $this->getTable();
        $hasRegionId = static::tableHasColumn($table, 'region_id');
        $hasCompanyId = static::tableHasColumn($table, 'company_id');
        $hasRouterId = static::tableHasColumn($table, 'router_id');
        $hasOdpId = static::tableHasColumn($table, 'odp_id');
        $hasOltId = static::tableHasColumn($table, 'olt_id');
        $hasCustomerId = static::tableHasColumn($table, 'customer_id');

        $companyId = $user->company_id;
        $coordinator = $user->coordinator;
        $regionId = $coordinator?->region_id;
        $routerId = $coordinator?->router_id;

        if ($regionId || $companyId || $routerId) {
            $query->where(function (Builder $q) use (
                $table, $hasRegionId, $hasCompanyId, $hasRouterId, $hasOdpId, $hasOltId, $hasCustomerId,
                $regionId, $companyId, $routerId
            ) {
                $appliedWhere = false;

                if ($regionId && $hasRegionId) {
                    $q->where("{$table}.region_id", $regionId);
                    $appliedWhere = true;
                }

                if ($routerId && $hasRouterId) {
                    $appliedWhere ? $q->orWhere("{$table}.router_id", $routerId) : $q->where("{$table}.router_id", $routerId);
                    $appliedWhere = true;
                }

                if ($companyId && $hasCompanyId) {
                    $method = $appliedWhere ? 'orWhere' : 'where';
                    $q->{$method}("{$table}.company_id", $companyId);
                    $appliedWhere = true;
                }

                if ($regionId && ! $hasRegionId && $hasOdpId) {
                    $method = $appliedWhere ? 'orWhereHas' : 'whereHas';
                    $q->{$method}('odp', function (Builder $subQ) use ($regionId) {
                        $subQ->where('region_id', $regionId);
                    });
                    $appliedWhere = true;
                }

                if ($regionId && ! $hasRegionId && $hasOltId) {
                    $method = $appliedWhere ? 'orWhereHas' : 'whereHas';
                    $q->{$method}('olt', function (Builder $subQ) use ($regionId) {
                        if (static::tableHasColumn('olts', 'region_id')) {
                            $subQ->where('region_id', $regionId);
                        }
                    });
                    $appliedWhere = true;
                }

                if ($regionId && ! $hasRegionId && $hasCustomerId) {
                    $method = $appliedWhere ? 'orWhereHas' : 'whereHas';
                    $q->{$method}('customer', function (Builder $subQ) use ($regionId) {
                        if (static::tableHasColumn('customers', 'region_id')) {
                            $subQ->where('region_id', $regionId);
                        }
                    });
                    $appliedWhere = true;
                }

                if ($companyId && ! $hasCompanyId && $hasCustomerId) {
                    $method = $appliedWhere ? 'orWhereHas' : 'whereHas';
                    $q->{$method}('customer', function (Builder $subQ) use ($companyId) {
                        if (static::tableHasColumn('customers', 'company_id')) {
                            $subQ->where('company_id', $companyId);
                        }
                    });
                    $appliedWhere = true;
                }

                if (! $appliedWhere && $companyId) {
                    $q->whereRaw('1 = 1');
                }
            });
        }

        return $query;
    }

    protected static function isUserSuperAdmin(User $user): bool
    {
        $superAdminRoles = config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']);

        return $user->hasAnyRole($superAdminRoles);
    }

    protected static function tableHasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = "{$table}.{$column}";
        if (! array_key_exists($key, $cache)) {
            $cache[$key] = Schema::hasColumn($table, $column);
        }

        return $cache[$key];
    }
}
