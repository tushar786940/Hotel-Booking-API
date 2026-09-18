<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends a booking confirmation to the guest
 *
 * ShouldQueue = this notification is sent in the BACKGROUND
 * WHY? Sending emails takes 1-3 seconds. We don't want the user
 * waiting for the email to send before they see their booking.
 *
 * With queues:
 *   User clicks "Book" → Booking created → Response sent (200ms)
 *   Meanwhile, in background: Email is being sent (2 seconds)
 *
 * Without queues:
 *   User clicks "Book" → Booking created → Email sent → Response sent (2.5 seconds)
 */
class BookingConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    /**
     * Which channels to send this notification through?
     *
     * 'mail'     = Send an email
     * 'database' = Store in notifications table (for in-app notifications)
     *
     * You could also add: 'sms', 'slack', 'broadcast' (websockets)
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Build the email content
     *
     * MailMessage provides a fluent API to build pretty emails
     * without writing HTML.
     */
    public function toMail($notifiable): MailMessage
    {
        $booking = $this->booking;

        return (new MailMessage)
            ->subject("Booking Confirmation - {$booking->booking_reference}")
            ->greeting("Hello {$notifiable->name}! 👋")
            ->line('Your booking has been received successfully!')
            ->line('')
            ->line("🏨 **Hotel:** {$booking->hotel->name}")
            ->line("🚪 **Room:** {$booking->room->roomType->name} (#{$booking->room->room_number})")
            ->line("📅 **Check-in:** {$booking->check_in->format('l, M d, Y')}")
            ->line("📅 **Check-out:** {$booking->check_out->format('l, M d, Y')}")
            ->line("🌙 **Nights:** {$booking->nights}")
            ->line("👥 **Guests:** {$booking->guests_count}")
            ->line("💰 **Total:** \${$booking->total_price}")
            ->line("📋 **Reference:** {$booking->booking_reference}")
            ->line('')
            ->action('View Your Booking', url("/api/v1/bookings/{$booking->id}"))
            ->line('')
            ->line('Please complete your payment to confirm the booking.')
            ->salutation('Thank you for choosing us! 🎉');
    }

    /**
     * Store notification in database
     *
     * This is what appears in the "bell icon" notifications
     * in a frontend application.
     */
    public function toArray($notifiable): array
    {
        return [
            'type'              => 'booking_confirmation',
            'booking_id'        => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'hotel_name'        => $this->booking->hotel->name,
            'check_in'          => $this->booking->check_in->format('Y-m-d'),
            'check_out'         => $this->booking->check_out->format('Y-m-d'),
            'total_price'       => $this->booking->total_price,
            'message'           => "Your booking at {$this->booking->hotel->name} has been received!",
        ];
    }
}
