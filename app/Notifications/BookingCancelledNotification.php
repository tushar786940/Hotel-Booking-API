<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Booking Cancelled - {$this->booking->booking_reference}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your booking has been cancelled.")
            ->line("**Reference:** {$this->booking->booking_reference}")
            ->line("**Hotel:** {$this->booking->hotel->name}")
            ->line("If you made a payment, a refund will be processed within 5-10 business days.")
            ->line('We hope to see you again soon!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'              => 'booking_cancelled',
            'booking_id'        => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'message'           => "Your booking {$this->booking->booking_reference} has been cancelled.",
        ];
    }
}