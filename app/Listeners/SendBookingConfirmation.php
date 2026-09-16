<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Notifications\BookingConfirmationNotification;

/**
 * Listener: reacts to BookingCreated event
 *
 * When a booking is created, send confirmation to the guest
 */
class SendBookingConfirmation
{
    /**
     * Handle the event
     *
     * @param BookingCreated $event - Contains the booking data
     */
    public function handle(BookingCreated $event): void
    {
        // Load the relationship so notification has hotel name
        $event->booking->load('hotel', 'room.roomType');

        // Send notification to the user who made the booking
        $event->booking->user->notify(
            new BookingConfirmationNotification($event->booking)
        );
    }
}
