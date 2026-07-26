<?php

namespace App\Observers;

use App\Models\Employee;
use App\Services\EmployeeSyncService;

class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        app(EmployeeSyncService::class)->syncFromEmployee($employee);
    }

    public function updated(Employee $employee): void
    {
        app(EmployeeSyncService::class)->syncFromEmployee($employee);
    }

    public function deleted(Employee $employee): void
    {
        if ($employee->user_id) {
            app(EmployeeSyncService::class)->unlinkUser($employee->user_id);
        }
    }
}
