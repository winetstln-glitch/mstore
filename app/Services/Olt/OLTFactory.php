<?php
// app/Services/OLT/OLTFactory.php

namespace App\Services\Olt;

use App\Models\OLT;
use App\Services\Olt\Contracts\OLTDriverInterface;

class OLTFactory
{
    protected array $drivers = [];

    public function __construct()
    {
        $this->register('hsgq', \App\Services\Olt\Drivers\HSGQDriver::class);
        $this->register('cdata', \App\Services\Olt\Drivers\CDataDriver::class);
        $this->register('huawei', \App\Services\Olt\Drivers\HuaweiDriver::class);
        $this->register('zte', \App\Services\Olt\Drivers\ZTEDriver::class);
        $this->register('fiberhome', \App\Services\Olt\Drivers\FiberHomeDriver::class);
        $this->register('nokia', \App\Services\Olt\Drivers\NokiaDriver::class);
    }

    public function register(string $vendor, string $driverClass): void
    {
        $this->drivers[strtolower($vendor)] = $driverClass;
    }

    public function create(OLT $olt): OLTDriverInterface
    {
        $vendor = strtolower($olt->vendor);
        $driverClass = $this->drivers[$vendor] ?? null;
        
        if (!$driverClass) {
            throw new \InvalidArgumentException("No driver for vendor: {$vendor}");
        }
        
        return new $driverClass(
            $olt->ip_address,
            $olt->read_community ?? 'public',
            $olt->write_community
        );
    }

    public function createFromArray(array $config): OLTDriverInterface
    {
        $vendor = strtolower($config['vendor'] ?? '');
        $driverClass = $this->drivers[$vendor] ?? null;
        
        if (!$driverClass) {
            throw new \InvalidArgumentException("No driver for vendor: {$vendor}");
        }
        
        return new $driverClass(
            $config['ip_address'],
            $config['read_community'] ?? 'public',
            $config['write_community'] ?? null
        );
    }
}