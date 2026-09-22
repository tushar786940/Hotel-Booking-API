<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Notifications\InvoiceEmailNotification;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;

/**
 * Automatically sends the invoice when payment is completed
 *
 * This listener reacts to the PaymentCompleted event.
 * It's registered in EventServiceProvider alongside SendPaymentReceipt.
 *
 * Flow:
 *   Stripe webhook → PaymentService::confirmPayment()
 *   → event(new PaymentCompleted($payment))
 *   → SendPaymentReceipt (sends receipt email)
 *   → SendInvoiceAfterPayment (sends invoice PDF) ← THIS
 */
class SendInvoiceAfterPayment
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        try {
            $booking = $event->payment->booking;
            $booking->load(['user', 'hotel', 'room.roomType', 'payment']);

            // Save a copy to storage for records
            $this->invoiceService->save($booking);

            // Email the invoice to the guest
            $booking->user->notify(
                new InvoiceEmailNotification($booking, $this->invoiceService)
            );

            Log::info('Invoice sent', [
                'booking' => $booking->booking_reference,
                'email'   => $booking->user->email,
            ]);
        } catch (\Exception $e) {
            // Log the error but don't fail the payment
            // The user can always download the invoice later
            Log::error('Failed to send invoice', [
                'error'   => $e->getMessage(),
                'booking' => $event->payment->booking_id,
            ]);
        }
    }
}
