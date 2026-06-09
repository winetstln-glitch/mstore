<?php

namespace App\Providers;

use App\Events\PaymentProcessed;
use App\Events\TicketAssigned;
use App\Events\WhatsApp\WhatsAppAnalyticsObserved;
use App\Listeners\ProcessPaymentSuccess;
use App\Listeners\SendTicketAssignedNotification;
use App\Listeners\WhatsApp\RecordWhatsAppAnalyticsObserved;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentProcessed::class => [
            ProcessPaymentSuccess::class,
        ],
        TicketAssigned::class => [
            SendTicketAssignedNotification::class,
        ],
        WhatsAppAnalyticsObserved::class => [
            RecordWhatsAppAnalyticsObserved::class,
        ],
    ];
}

