<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Permission;
use App\Models\Transaction;
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
            if (Schema::hasTable('accounts')) {
                Account::ensureDefaultChart();
            }

            if (Schema::hasTable('transactions') && Schema::hasTable('journals')) {
                $this->backfillFinanceJournals();
            }

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
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
        }
    }

    private function backfillFinanceJournals(): void
    {
        $key = 'finance_journal_backfill_batch';
        if (! Cache::add($key, true, now()->addMinutes(5))) {
            return;
        }

        Transaction::query()
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('journals')
                    ->whereColumn('journals.source_id', 'transactions.id')
                    ->where('journals.source_type', 'finance_transaction');
            })
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->each(function (Transaction $transaction): void {
                $transaction->syncAccountingJournal();
            });
    }
}
