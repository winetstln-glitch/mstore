<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('user.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermission('user.view') || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('user.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermission('user.update') || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermission('user.delete');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasPermission('user.restore');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasPermission('user.force_delete');
    }
}
