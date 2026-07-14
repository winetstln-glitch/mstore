<?php

namespace App\Providers;

use App\Contracts\Network\NetworkProviderInterface;
use App\Services\Network\Adapters\DummyAdapter;
use Illuminate\Support\ServiceProvider;

class NetworkServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(NetworkProviderInterface::class, function ($app) {
            // TODO: Ganti ke MikroTikAdapter ketika siap
            return new DummyAdapter();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}