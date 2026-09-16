<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Notifications\PaymentReceiptNotification;

class SendPaymentReceipt
{
    public function handle(PaymentCompleted $event): void
    {
        $event->payment->load('booking.hotel');

        $event->payment->booking->user->notify(
            new PaymentReceiptNotification($event->payment)
        );
    }
}
