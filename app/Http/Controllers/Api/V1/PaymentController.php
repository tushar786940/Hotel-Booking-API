<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Initiate payment for a booking
     *
     * POST /api/v1/bookings/5/pay
     *
     * Returns a Stripe client_secret that the frontend uses
     * to show Stripe's payment form.
     */
    public function processPayment(Request $request, Booking $booking): JsonResponse
    {
        // Authorization
        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        // Can only pay for pending bookings
        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'This booking cannot be paid for. Current status: ' . $booking->status,
            ], 422);
        }

        // Check if already has a pending/completed payment
        if ($booking->payment && $booking->payment->status === 'completed') {
            return response()->json([
                'message' => 'This booking has already been paid for.',
            ], 422);
        }

        $paymentData = $this->paymentService->createPaymentIntent($booking);

        return response()->json([
            'message' => 'Payment intent created. Use client_secret to complete payment.',
            'data'    => $paymentData,
        ]);
    }

    /**
     * Check payment status
     *
     * GET /api/v1/bookings/5/payment-status
     */
    public function status(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            abort(403);
        }

        $booking->load('payment');

        return response()->json([
            'data' => [
                'booking_reference' => $booking->booking_reference,
                'booking_status'    => $booking->status,
                'payment'           => $booking->payment ? [
                    'status'         => $booking->payment->status,
                    'amount'         => $booking->payment->amount,
                    'method'         => $booking->payment->method,
                    'transaction_id' => $booking->payment->transaction_id,
                    'paid_at'        => $booking->payment->paid_at?->toISOString(),
                ] : null,
            ],
        ]);
    }
}