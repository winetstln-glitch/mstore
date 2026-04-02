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
            if (Schema::hasTable('permissions')) {
                $permissions = Cache::remember('all_permissions', 3600, function () {
                    return Permission::get();
                });
                // $permissions = Permission::get();

                $permissions->map(function ($permission) {
                    Gate::define($permission->name, function ($user) use ($permission) {
                        return $user->hasPermission($permission->name);
                    });
                });
            }
            if (Schema::hasTable('users')) {
                User::observe(UserObserver::class);
            }
            if (Schema::hasTable('wash_employees')) {
                WashEmployee::observe(WashEmployeeObserver::class);
            }
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
        }
    }
}
