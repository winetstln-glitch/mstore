<?php

namespace App\Observers;

use App\Models\WashEmployee;
use App\Services\EmployeeSyncService;

class WashEmployeeObserver
{
    public function created(WashEmployee $washEmployee): void
    {
        app(EmployeeSyncService::class)->syncFromWashEmployee($washEmployee);
    }

    public function updated(WashEmployee $washEmployee): void
    {
        app(EmployeeSyncService::class)->syncFromWashEmployee($washEmployee);
    }

    public function deleted(WashEmployee $washEmployee): void
    {
        app(EmployeeSyncService::class)->unlinkWashEmployee($washEmployee->id);
    }
}
