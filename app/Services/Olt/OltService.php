<?php

namespace App\Services\Olt;

use App\Models\Olt;
use App\Services\Olt\Drivers\CDataDriver;
use App\Services\Olt\Drivers\HsgqDriver;
use App\Services\Olt\Drivers\SnmpDriver;
use Exception;

class OltService
{
    public function getDriver(Olt $olt): OltDriverInterface
    {
        // Prefer SNMP if community is set
        if (!empty($olt->snmp_community)) {
            return new SnmpDriver;
        }

        $brand = strtolower($olt->brand);

        switch ($brand) {
            case 'hsgq':
                return new HsgqDriver;
            case 'cdata':
            case 'c-data':
                return new CDataDriver;
            case 'vsol':
            case 'zte':
            case 'huawei':
                return new HsgqDriver;
            default:
                return new HsgqDriver;
        }
    }

    public function testLogin(Olt $olt, $timeout = 10)
    {
        try {
            $driver = $this->getDriver($olt);
            $driver->connect($olt, $timeout);
            $driver->disconnect();

            return ['success' => true, 'message' => 'Login successful!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
