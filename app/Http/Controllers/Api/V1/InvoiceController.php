<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\InvoiceEmailNotification;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Handles PDF invoice generation and delivery
 *
 * Endpoints:
 * - Download invoice (guest & admin)
 * - View invoice in browser (guest & admin)
 * - Email invoice (guest & admin)
 */
class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Download the invoice PDF
     *
     * GET /api/v1/bookings/{booking}/invoice/download
     *
     * Returns a file download response.
     * Browser will prompt "Save As..." dialog.
     *
     * Response Headers:
     *   Content-Type: application/pdf
     *   Content-Disposition: attachment; filename="Invoice-BK-xxx.pdf"
     */
    public function download(Request $request, Booking $booking): Response
    {
        // ─── Authorization ───
        // Guest can download their own invoice
        // Hotel owner can download invoices for their hotel
        $this->authorizeBookingAccess($request, $booking);

        return $this->invoiceService->download($booking);
    }

    /**
     * View the invoice PDF in browser
     *
     * GET /api/v1/bookings/{booking}/invoice
     *
     * Returns the PDF inline (displayed in browser's PDF viewer).
     * No download prompt.
     *
     * Response Headers:
     *   Content-Type: application/pdf
     *   Content-Disposition: inline; filename="Invoice-BK-xxx.pdf"
     */
    public function view(Request $request, Booking $booking): Response
    {
        $this->authorizeBookingAccess($request, $booking);

        return $this->invoiceService->stream($booking);
    }

    /**
     * Email the invoice to the guest
     *
     * POST /api/v1/bookings/{booking}/invoice/email
     *
     * Generates the PDF and sends it as an email attachment.
     * Useful when the guest lost the original email.
     */
    public function email(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBookingAccess($request, $booking);

        // Check if booking has a payment (can't invoice unpaid bookings)
        if (!$booking->payment || $booking->payment->status !== 'completed') {
            return response()->json([
                'message' => 'Invoice can only be generated for paid bookings.',
            ], 422);
        }

        // Send the invoice email
        // The notification handles PDF generation and attachment
        $booking->user->notify(
            new InvoiceEmailNotification($booking, $this->invoiceService)
        );

        return response()->json([
            'message' => "Invoice sent to {$booking->user->email}",
        ]);
    }

    /**
     * Regenerate invoice (admin/hotel owner only)
     *
     * POST /api/v1/manage/bookings/{booking}/invoice/regenerate
     *
     * Useful when hotel details changed after the original invoice.
     */
    public function regenerate(Request $request, Booking $booking): Response
    {
        // Only hotel owner or admin
        if (
            $booking->hotel->user_id !== $request->user()->id
            && !$request->user()->hasRole('admin')
        ) {
            abort(403);
        }

        return $this->invoiceService->download($booking);
    }

    /**
     * Authorization helper
     *
     * Checks if the current user is allowed to access this booking's invoice.
     *
     * Rules:
     * 1. Guest can access their OWN bookings
     * 2. Hotel owner can access bookings for THEIR hotels
     * 3. Admin can access ANY booking
     */
    protected function authorizeBookingAccess(Request $request, Booking $booking): void
    {
        $user = $request->user();

        // Admin can access everything
        if ($user->hasRole('admin')) {
            return;
        }

        // Guest can access their own bookings
        if ($booking->user_id === $user->id) {
            return;
        }

        // Hotel owner can access their hotel's bookings
        if ($user->hasRole('hotel-owner') && $booking->hotel->user_id === $user->id) {
            return;
        }

        abort(403, 'You are not authorized to access this invoice.');
    }
}
