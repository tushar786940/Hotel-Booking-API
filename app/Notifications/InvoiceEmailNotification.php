<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends the invoice PDF as an email attachment
 *
 * This is triggered after successful payment.
 * The guest receives a professional email with the PDF attached.
 */
class InvoiceEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        protected InvoiceService $invoiceService
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $booking = $this->booking;

        // Generate the raw PDF content for attachment
        $pdfContent = $this->invoiceService->getRawContent($booking);
        $filename   = "Invoice-{$booking->booking_reference}.pdf";

        return (new MailMessage)
            ->subject("Your Invoice - {$booking->booking_reference}")
            ->greeting("Dear {$notifiable->name},")
            ->line('Thank you for your payment! Please find your invoice attached.')
            ->line('')
            ->line("**Booking Reference:** {$booking->booking_reference}")
            ->line("**Hotel:** {$booking->hotel->name}")
            ->line("**Dates:** {$booking->check_in->format('M d')} – {$booking->check_out->format('M d, Y')}")
            ->line("**Total Paid:** \${$booking->total_price}")
            ->line('')
            ->line('If you have any questions, please contact us with your booking reference.')
            ->salutation('Best regards, ' . $booking->hotel->name)

            // ─── ATTACH THE PDF ───
            // attachData() attaches raw content as a file
            // Parameters: content, filename, options
            ->attachData(
                $pdfContent,
                $filename,
                [
                    'mime' => 'application/pdf',
                ]
            );
    }
}
