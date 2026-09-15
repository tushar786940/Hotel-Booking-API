<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * This event is fired when a new booking is created.
 *
 * Events are simple classes that carry data.
 * They don't DO anything - they just announce something happened.
 */
class BookingCreated
{
    use Dispatchable;   // Allows: event(new BookingCreated($booking))
    use SerializesModels; // Safely serializes the Booking model for queues

    /**
     * PHP 8 constructor property promotion
     * This single line creates a public property AND assigns it
     *
     * Same as:
     *   public Booking $booking;
     *   public function __construct(Booking $booking) {
     *       $this->booking = $booking;
     *   }
     */
    public function __construct(public Booking $booking) {}
}
