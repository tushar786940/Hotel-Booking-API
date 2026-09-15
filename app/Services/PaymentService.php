<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Events\PaymentCompleted;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\DB;

/**
 * Handles payment processing with Stripe
 *
 * HOW STRIPE WORKS:
 *
 * 1. Our server creates a "PaymentIntent" (tells Stripe how much to charge)
 * 2. Stripe returns a "client_secret"
 * 3. Our frontend uses client_secret to show Stripe's payment form
 * 4. Customer enters card details DIRECTLY to Stripe (we never see them!)
 * 5. Stripe charges the card
 * 6. Stripe sends us a webhook: "Payment succeeded!"
 * 7. We update our database
 *
 * WHY this flow? PCI compliance. We never handle credit card numbers,
 * so we don't need expensive security certifications.
 */
class PaymentService
{
    public function __construct()
    {
        // Tell Stripe SDK which account to use
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe PaymentIntent
     *
     * This is step 1 of the payment flow.
     * Frontend will use the returned client_secret to complete payment.
     */
    public function createPaymentIntent(Booking $booking): array
    {
        // Create PaymentIntent on Stripe
        $paymentIntent = PaymentIntent::create([
            // Stripe uses CENTS, not dollars
            // $150.00 → 15000 cents
            'amount'   => (int) ($booking->total_price * 100),
            'currency' => 'usd',
            // Metadata = extra info attached to the payment
            // Useful for looking up payments in Stripe's dashboard
            'metadata' => [
                'booking_id'        => $booking->id,
                'booking_reference' => $booking->booking_reference,
            ],
        ]);

        // Save payment record in OUR database
        Payment::create([
            'booking_id'     => $booking->id,
            'amount'         => $booking->total_price,
            'currency'       => 'usd',
            'method'         => 'stripe',
            'transaction_id' => $paymentIntent->id,
            'status'         => 'pending', // Will be updated by webhook
        ]);

        // Return to frontend
        return [
            'client_secret' => $paymentIntent->client_secret,
            'payment_id'    => $paymentIntent->id,
            'amount'        => $booking->total_price,
        ];
    }

    /**
     * Confirm payment (called by Stripe webhook)
     *
     * When Stripe successfully charges the card, it sends us
     * a webhook notification. This method processes that notification.
     */
    public function confirmPayment(string $transactionId): Payment
    {
        return DB::transaction(function () use ($transactionId) {
            // Find our payment record by Stripe's transaction ID
            $payment = Payment::where('transaction_id', $transactionId)
                              ->firstOrFail();

            // Update payment status
            $payment->update([
                'status'  => 'completed',
                'paid_at' => now(),
            ]);

            // Update booking status from 'pending' to 'confirmed'
            $payment->booking->update(['status' => 'confirmed']);

            // Notify the system
            event(new PaymentCompleted($payment));

            return $payment;
        });
    }

    /**
     * Process refund
     */
    public function refundPayment(Payment $payment): Payment
    {
        if ($payment->status !== 'completed') {
            throw new \Exception('Can only refund completed payments.');
        }

        // Create refund on Stripe
        \Stripe\Refund::create([
            'payment_intent' => $payment->transaction_id,
        ]);

        // Update our records
        $payment->update(['status' => 'refunded']);
        $payment->booking->update(['status' => 'refunded']);

        return $payment;
    }
}