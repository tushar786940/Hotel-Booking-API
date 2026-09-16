<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $booking = $this->payment->booking;

        return (new MailMessage)
            ->subject("Payment Receipt - {$booking->booking_reference}")
            ->greeting("Payment Successful! ✅")
            ->line("Thank you for your payment.")
            ->line("**Amount:** \${$this->payment->amount}")
            ->line("**Booking:** {$booking->booking_reference}")
            ->line("**Hotel:** {$booking->hotel->name}")
            ->line("**Transaction ID:** {$this->payment->transaction_id}")
            ->line('')
            ->line('Your booking is now confirmed. See you soon!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'              => 'payment_receipt',
            'payment_id'        => $this->payment->id,
            'amount'            => $this->payment->amount,
            'booking_reference' => $this->payment->booking->booking_reference,
            'message'           => "Payment of \${$this->payment->amount} received!",
        ];
    }
}