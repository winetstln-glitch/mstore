<?php

namespace App\Services\Olt;

use App\Models\Olt;

interface OltDriverInterface
{
    public function connect(Olt $olt, $timeout = 10);
    public function getOnus(); // Returns array of ONU data
    public function getSystemInfo(); // Returns array with uptime, version, temp, cpu
    public function disconnect();
}
