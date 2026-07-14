<?php
// app/Providers/OLTDriverServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\OLT\OLTFactory;
use App\Services\OLT\Drivers\HSGQDriver2;

class OLTDriverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OLTFactory::class, function ($app) {
            $factory = new OLTFactory();
            
            // Register all vendor drivers
            $factory->register('hsgq', \App\Services\OLT\Drivers\HSGQDriver::class);
            $factory->register('huawei', \App\Services\OLT\Drivers\HuaweiDriver::class);
            $factory->register('zte', \App\Services\OLT\Drivers\ZTEDriver::class);
            $factory->register('fiberhome', \App\Services\OLT\Drivers\FiberHomeDriver::class);
            $factory->register('nokia', \App\Services\OLT\Drivers\NokiaDriver::class);
            $factory->register('cisco', \App\Services\OLT\Drivers\CiscoDriver::class);
            $factory->register('calix', \App\Services\OLT\Drivers\CalixDriver::class);
            
            return $factory;
        });
    }
}