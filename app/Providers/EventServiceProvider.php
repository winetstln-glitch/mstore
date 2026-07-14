<?php

namespace App\Providers;

use App\Events\AtkTransactionCreated;
use App\Events\ExpenseApproved;
use App\Events\GeneralTransactionCreated;
use App\Events\InvoicePaidEvent;
use App\Events\PaymentProcessed;
use App\Events\TicketAssigned;
use App\Events\WashTransactionCreated;
use App\Events\WhatsApp\WhatsAppAnalyticsObserved;
use App\Listeners\AccountingEventListener;
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
        
        // ERP Core Events
        GeneralTransactionCreated::class => [
            [AccountingEventListener::class, 'handleGeneralTransactionCreated'],
        ],
        InvoicePaidEvent::class => [
            [AccountingEventListener::class, 'handleInvoicePaid'],
        ],
        WashTransactionCreated::class => [
            [AccountingEventListener::class, 'handleWashTransactionCreated'],
        ],
        AtkTransactionCreated::class => [
            [AccountingEventListener::class, 'handleAtkTransactionCreated'],
        ],
        ExpenseApproved::class => [
            [AccountingEventListener::class, 'handleExpenseApproved'],
        ],
    ];
}

