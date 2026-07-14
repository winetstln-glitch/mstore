<?php

namespace Modules\Network\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Network\Events\Domain\CustomerActivated as DomainCustomerActivated;
use Modules\Network\Events\Domain\CustomerSuspended as DomainCustomerSuspended;
use Modules\Network\Events\Domain\CustomerUnsuspended as DomainCustomerUnsuspended;
use Modules\Network\Events\CustomerCreated;
use Modules\Network\Events\ProvisioningRequested;
use Modules\Network\Events\ProfileChanged;
use Modules\Network\Listeners\AuditLogListener;
use Modules\Network\Listeners\ProvisionCustomerListener;
use Modules\Network\Listeners\SuspendCustomerListener;
use Modules\Network\Listeners\SyncCustomerListener;
use Modules\Network\Listeners\UnsuspendCustomerListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        CustomerCreated::class => [
            SyncCustomerListener::class,
            AuditLogListener::class,
        ],
        DomainCustomerActivated::class => [
            ProvisionCustomerListener::class,
            AuditLogListener::class,
        ],
        DomainCustomerSuspended::class => [
            SuspendCustomerListener::class,
            AuditLogListener::class,
        ],
        DomainCustomerUnsuspended::class => [
            UnsuspendCustomerListener::class,
            AuditLogListener::class,
        ],
        ProvisioningRequested::class => [
            AuditLogListener::class,
        ],
        ProfileChanged::class => [
            AuditLogListener::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
