<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;

/**
 * Handles Stripe webhooks
 *
 * WHAT IS A WEBHOOK?
 * Instead of us asking Stripe "Did the payment succeed?" every second,
 * Stripe TELLS US when something happens by sending an HTTP request
 * to this endpoint.
 *
 * Flow:
 * 1. Customer pays on Stripe
 * 2. Stripe processes payment
 * 3. Stripe sends POST request to our /api/v1/webhooks/stripe
 * 4. We update our database accordingly
 */
class WebhookController extends Controller
{
    public function handleStripe(
        Request $request,
        PaymentService $paymentService
    ): JsonResponse {
        // Get raw request body (not parsed JSON)
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        /**
         * VERIFY THE WEBHOOK IS FROM STRIPE
         *
         * WHY? Anyone could send a POST request to this endpoint
         * pretending to be Stripe. We verify the signature to make
         * sure it's genuinely from Stripe.
         */
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed.', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error.', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Webhook error'], 400);
        }

        // Handle different event types
        switch ($event->type) {

            case 'payment_intent.succeeded':
                /**
                 * Payment was successful!
                 * Update our payment record and confirm the booking.
                 */
                $paymentIntent = $event->data->object;
                $paymentService->confirmPayment($paymentIntent->id);

                Log::info('Payment succeeded', [
                    'payment_intent_id' => $paymentIntent->id,
                ]);
                break;

            case 'payment_intent.payment_failed':
                /**
                 * Payment failed (declined card, etc.)
                 * Update our payment record to reflect failure.
                 */
                $paymentIntent = $event->data->object;

                Log::warning('Payment failed', [
                    'payment_intent_id' => $paymentIntent->id,
                    'error' => $paymentIntent->last_payment_error?->message ?? 'Unknown',
                ]);
                break;

            default:
                // Log unhandled event types for debugging
                Log::info('Unhandled Stripe event', ['type' => $event->type]);
        }

        // Always return 200 to acknowledge receipt
        // If we return an error, Stripe will keep retrying
        return response()->json(['status' => 'ok']);
    }
}