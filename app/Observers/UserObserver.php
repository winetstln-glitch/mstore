<?php

namespace App\Observers;

use App\Models\User;
use App\Services\EmployeeSyncService;

class UserObserver
{
    public function created(User $user): void
    {
        app(EmployeeSyncService::class)->syncFromUser($user);
    }

    public function updated(User $user): void
    {
        app(EmployeeSyncService::class)->syncFromUser($user);
    }

    public function deleted(User $user): void
    {
        app(EmployeeSyncService::class)->unlinkUser($user->id);
    }
}
