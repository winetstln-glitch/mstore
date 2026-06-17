<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ticket.view');
    }

    public function view(User $user, Ticket $model): bool
    {
        return $user->hasPermission('ticket.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('ticket.create');
    }

    public function update(User $user, Ticket $model): bool
    {
        return $user->hasPermission('ticket.update');
    }

    public function delete(User $user, Ticket $model): bool
    {
        return $user->hasPermission('ticket.delete');
    }

    public function restore(User $user, Ticket $model): bool
    {
        return $user->hasPermission('ticket.restore');
    }

    public function forceDelete(User $user, Ticket $model): bool
    {
        return $user->hasPermission('ticket.force_delete');
    }

    public function assign(User $user, Ticket $model): bool
    {
        return $user->hasPermission('ticket.assign');
    }

    public function close(User $user, Ticket $model): bool
    {
        return $user->hasPermission('ticket.close');
    }
}
