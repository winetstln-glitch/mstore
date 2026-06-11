<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WashEmployee;
use App\Observers\CustomerObserver;
use App\Observers\TicketObserver;
use App\Observers\UserObserver;
use App\Observers\WashEmployeeObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
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
        $this->app->bind(
            \App\Repositories\Contracts\NocMetricSnapshotRepositoryInterface::class,
            \App\Repositories\Eloquent\NocMetricSnapshotRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\WhatsAppAnalyticsEventRepositoryInterface::class,
            \App\Repositories\Eloquent\WhatsAppAnalyticsEventRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\KnowledgeBaseRepositoryInterface::class,
            \App\Repositories\Eloquent\KnowledgeBaseRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\SlaRuleRepositoryInterface::class,
            \App\Repositories\Eloquent\SlaRuleRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\SlaBreachRepositoryInterface::class,
            \App\Repositories\Eloquent\SlaBreachRepository::class
        );
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
                $isAdmin = $authUser->hasRole(Role::ADMIN);
                $roleId = $authUser->role_id;
                $runningUnitTests = app()->runningUnitTests();

                $permissionMap = [];
                if (! $isAdmin) {
                    $computePermissionMap = function () use ($roleId): array {
                        $role = Role::query()
                            ->whereKey($roleId)
                            ->with(['permissions:id,name'])
                            ->first();

                        return ($role?->permissions?->pluck('name')->flip()->all()) ?? [];
                    };

                    $permissionMap = $runningUnitTests
                        ? $computePermissionMap()
                        : Cache::remember("sidebar.permission_map.role.{$roleId}", 300, $computePermissionMap);
                }

                $sidebarMenu = $runningUnitTests
                    ? \App\Support\Sidebar\SidebarMenu::tree()
                    : Cache::rememberForever('sidebar.menu.tree.v11', function () {
                        return \App\Support\Sidebar\SidebarMenu::tree();
                    });
                $unreadNotificationCount = $authUser->unreadNotifications()->count();
                $unreadNotifications = $authUser->unreadNotifications()->latest()->limit(10)->get();
                
                $view->with([
                    'authUser' => $authUser,
                    'isAdmin' => $isAdmin,
                    'permissionMap' => $permissionMap,
                    'sidebarMenu' => $sidebarMenu,
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

            $hasTicketsTable = Cache::rememberForever('has_tickets_table', function () {
                return Schema::hasTable('tickets');
            });
            if ($hasTicketsTable) {
                Ticket::observe(TicketObserver::class);
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
