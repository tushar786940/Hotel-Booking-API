<?php

namespace App\Providers;

use App\Events\BookingCreated;
use App\Events\BookingCancelled;
use App\Events\PaymentCompleted;
use App\Listeners\SendBookingConfirmation;
use App\Listeners\SendCancellationNotification;
use App\Listeners\SendInvoiceAfterPayment;
use App\Listeners\SendPaymentReceipt;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event-to-Listener mapping
     *
     * This tells Laravel:
     * "When BookingCreated event fires, run SendBookingConfirmation listener"
     *
     * One event can have MULTIPLE listeners!
     * We could add more listeners later without touching the event.
     */
    protected $listen = [
        BookingCreated::class => [
            SendBookingConfirmation::class,
            // Could add more: UpdateAnalytics::class, NotifyHotelOwner::class
        ],
        BookingCancelled::class => [
            SendCancellationNotification::class,
        ],
        PaymentCompleted::class => [
            SendPaymentReceipt::class,
            SendInvoiceAfterPayment::class
        ],
    ];
}
