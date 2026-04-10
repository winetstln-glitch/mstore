<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use App\Models\WashEmployee;
use App\Observers\UserObserver;
use App\Observers\WashEmployeeObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        $shouldForceHttps = app()->environment('production') || (bool) env('FORCE_HTTPS', false);
        if ($shouldForceHttps) {
            URL::forceScheme('https');
            $root = config('app.url');
            if (is_string($root) && strlen($root) > 0) {
                URL::forceRootUrl($root);
            }
        }

        try {
            $hasPermissionsTable = Cache::rememberForever('has_permissions_table', function () {
                return Schema::hasTable('permissions');
            });

            if ($hasPermissionsTable) {
                // Use Gate::before for dynamic permission checking instead of defining hundreds of gates
                Gate::before(function ($user, $ability) {
                    if (method_exists($user, 'hasPermission')) {
                        return $user->hasPermission($ability) ?: null;
                    }
                });
            }

            $hasUsersTable = Cache::rememberForever('has_users_table', function () {
                return Schema::hasTable('users');
            });

            if ($hasUsersTable) {
                User::observe(UserObserver::class);
            }

            $hasWashEmployeesTable = Cache::rememberForever('has_wash_employees_table', function () {
                return Schema::hasTable('wash_employees');
            });

            if ($hasWashEmployeesTable) {
                WashEmployee::observe(WashEmployeeObserver::class);
            }
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
        }
    }
}
