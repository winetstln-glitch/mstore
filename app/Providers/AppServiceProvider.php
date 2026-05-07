<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\User;
use App\Models\WashEmployee;
use App\Observers\CustomerObserver;
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

        // View Composers for Sidebar / Layout Data
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $authUser = Auth::user();
            if ($authUser) {
                // Eager load role and permissions once
                $authUser->loadMissing('role.permissions');
                
                $isAdmin = $authUser->hasRole('admin');
                $unreadNotificationCount = $authUser->unreadNotifications()->count();
                $unreadNotifications = $authUser->unreadNotifications()->latest()->limit(10)->get();
                
                $view->with([
                    'authUser' => $authUser,
                    'isAdmin' => $isAdmin,
                    'unreadNotificationCount' => $unreadNotificationCount,
                    'unreadNotifications' => $unreadNotifications,
                ]);
            }
        });

        // Dynamic Permissions from Database
        try {
            // Use Gate::before for dynamic permission checking
            Gate::before(function ($user, $ability) {
                if (method_exists($user, 'hasPermission')) {
                    return $user->hasPermission($ability) ?: null;
                }
            });

            $hasUsersTable = Cache::rememberForever('has_users_table', function () {
                return Schema::hasTable('users');
            });

            if ($hasUsersTable) {
                User::observe(UserObserver::class);
                Customer::observe(CustomerObserver::class);
            }

            $hasWashEmployeesTable = Cache::rememberForever('has_wash_employees_table', function () {
                return Schema::hasTable('wash_employees');
            });

            if ($hasWashEmployeesTable) {
                WashEmployee::observe(WashEmployeeObserver::class);
            }
        } catch (\Exception $e) {
            // Silently fail if table doesn't exist yet
        }
    }
}
