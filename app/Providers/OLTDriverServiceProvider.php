<?php
// app/Providers/OLTDriverServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Olt\OLTFactory;
use App\Services\Olt\Drivers\HSGQDriver2;

class OLTDriverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OLTFactory::class, function ($app) {
            $factory = new OLTFactory();
            
            // Register all vendor drivers
            $factory->register('hsgq', \App\Services\Olt\Drivers\HSGQDriver::class);
            $factory->register('huawei', \App\Services\Olt\Drivers\HuaweiDriver::class);
            $factory->register('zte', \App\Services\Olt\Drivers\ZTEDriver::class);
            $factory->register('fiberhome', \App\Services\Olt\Drivers\FiberHomeDriver::class);
            $factory->register('nokia', \App\Services\Olt\Drivers\NokiaDriver::class);
            $factory->register('cisco', \App\Services\Olt\Drivers\CiscoDriver::class);
            $factory->register('calix', \App\Services\Olt\Drivers\CalixDriver::class);
            
            return $factory;
        });
    }
}