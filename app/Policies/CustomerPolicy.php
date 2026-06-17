<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('customer.view');
    }

    public function view(User $user, Customer $model): bool
    {
        return $user->hasPermission('customer.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('customer.create');
    }

    public function update(User $user, Customer $model): bool
    {
        return $user->hasPermission('customer.update');
    }

    public function delete(User $user, Customer $model): bool
    {
        return $user->hasPermission('customer.delete');
    }

    public function restore(User $user, Customer $model): bool
    {
        return $user->hasPermission('customer.restore');
    }

    public function forceDelete(User $user, Customer $model): bool
    {
        return $user->hasPermission('customer.force_delete');
    }
}
