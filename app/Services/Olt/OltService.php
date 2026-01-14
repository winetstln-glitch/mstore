<?php

namespace App\Services\Olt;

use App\Models\Olt;
use App\Services\Olt\Drivers\HsgqDriver;
use App\Services\Olt\Drivers\CDataDriver;
use Exception;

class OltService
{
    public function getDriver(Olt $olt): OltDriverInterface
    {
        $brand = strtolower($olt->brand);

        switch ($brand) {
            case 'hsgq':
                return new HsgqDriver();
            case 'cdata':
            case 'c-data':
                return new CDataDriver();
            default:
                throw new Exception("Driver for brand {$brand} not implemented.");
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
