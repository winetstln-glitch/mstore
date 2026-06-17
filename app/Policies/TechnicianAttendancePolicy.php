<?php

namespace App\Policies;

use App\Models\TechnicianAttendance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TechnicianAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.view');
    }

    public function view(User $user, TechnicianAttendance $model): bool
    {
        return $user->hasPermission('attendance.view') || $user->id === $model->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('attendance.create');
    }

    public function update(User $user, TechnicianAttendance $model): bool
    {
        return $user->hasPermission('attendance.update') || $user->id === $model->user_id;
    }

    public function delete(User $user, TechnicianAttendance $model): bool
    {
        return $user->hasPermission('attendance.delete');
    }

    public function restore(User $user, TechnicianAttendance $model): bool
    {
        return $user->hasPermission('attendance.restore');
    }

    public function forceDelete(User $user, TechnicianAttendance $model): bool
    {
        return $user->hasPermission('attendance.force_delete');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('attendance.export');
    }
}
