<?php

namespace App\Observers;

use App\Models\Router;

class RouterObserver
{
    /**
     * Handle the Router "created" event.
     */
    public function created(Router $router): void
    {
        //
    }

    /**
     * Handle the Router "updated" event.
     */
    public function updated(Router $router): void
    {
        //
    }

    /**
     * Handle the Router "deleted" event.
     */
    public function deleted(Router $router): void
    {
        //
    }

    /**
     * Handle the Router "restored" event.
     */
    public function restored(Router $router): void
    {
        //
    }

    /**
     * Handle the Router "force deleted" event.
     */
    public function forceDeleted(Router $router): void
    {
        //
    }
}
