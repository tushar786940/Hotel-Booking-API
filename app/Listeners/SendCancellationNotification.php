<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Notifications\BookingCancelledNotification;

class SendCancellationNotification
{
    public function handle(BookingCancelled $event): void
    {
        $event->booking->load('hotel');

        $event->booking->user->notify(
            new BookingCancelledNotification($event->booking)
        );
    }
}
