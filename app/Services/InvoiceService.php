<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

/**
 * Handles PDF invoice generation for bookings
 *
 * WHY a service?
 * Invoice generation involves multiple steps:
 * 1. Gather all booking data
 * 2. Calculate price breakdown
 * 3. Render Blade template to HTML
 * 4. Convert HTML to PDF
 * 5. Save to storage (optional)
 * 6. Return the PDF for download/email
 *
 * Keeping this in a service means we can generate invoices
 * from controllers, commands, queues, or event listeners.
 */
class InvoiceService
{
    public function __construct(
        protected PricingService $pricingService
    ) {}

    /**
     * Generate a PDF invoice for a booking
     *
     * @param Booking $booking The booking to generate invoice for
     * @return \Barryvdh\DomPDF\PDF  The PDF instance (can be downloaded, streamed, or saved)
     */
    public function generate(Booking $booking): \Barryvdh\DomPDF\PDF
    {
        $booking->load([
            'user',
            'hotel',
            'room.roomType',
            'payment',
        ]);

        $priceBreakdown = $this->pricingService->getBreakdown(
            $booking->room->roomType,
            $booking->nights,
            $booking->guests_count
        );

        // Generate QR code locally as base64 PNG — no network call needed
        $qrCode = base64_encode(
            QrCode::format('svg')->size(100)->generate($booking->booking_reference)
        );

        $data = [
            'booking'        => $booking,
            'hotel'          => $booking->hotel,
            'priceBreakdown' => $priceBreakdown,
            'invoiceDate'    => Carbon::now(),
            'qrCode'         => $qrCode,
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false, // ✅ no longer needed
                'defaultFont'          => 'dejavusans',
                'dpi'                  => 150,
            ]);

        return $pdf;
    }

    /**
     * Generate and download the PDF
     *
     * This returns an HTTP response that triggers a file download
     * in the browser.
     *
     * @param Booking $booking
     * @param string  $filename Custom filename (optional)
     * @return \Illuminate\Http\Response
     */
    public function download(Booking $booking, ?string $filename = null): \Illuminate\Http\Response
    {
        $pdf = $this->generate($booking);

        // Default filename: Invoice-BK-A8Kx9mPq.pdf
        $filename = $filename ?? "Invoice-{$booking->booking_reference}.pdf";

        // download() returns a response with Content-Disposition: attachment
        // This tells the browser to download the file instead of displaying it
        return $pdf->download($filename);
    }

    /**
     * Generate and display the PDF inline in the browser
     *
     * Instead of downloading, the PDF opens in the browser's
     * built-in PDF viewer.
     *
     * @param Booking $booking
     * @return \Illuminate\Http\Response
     */
    public function stream(Booking $booking): \Illuminate\Http\Response
    {
        $pdf = $this->generate($booking);

        $filename = "Invoice-{$booking->booking_reference}.pdf";

        // stream() returns a response with Content-Disposition: inline
        // Browser displays the PDF instead of downloading
        return $pdf->stream($filename);
    }

    /**
     * Generate and save the PDF to storage
     *
     * WHY save to disk?
     * - Email attachments need a file path
     * - Avoid regenerating the same PDF multiple times
     * - Keep a permanent record for auditing
     *
     * @param Booking $booking
     * @return string  The storage path where the PDF was saved
     */
    public function save(Booking $booking): string
    {
        $pdf = $this->generate($booking);

        // Storage path: invoices/2025/01/BK-A8Kx9mPq.pdf
        // Organized by year/month for easy management
        $directory = 'invoices/' . now()->format('Y/m');
        $filename  = "{$booking->booking_reference}.pdf";

        $path      = "{$directory}/{$filename}";

        // Save the PDF content to the storage disk
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Get the raw PDF content as a string
     *
     * Useful for email attachments (Laravel's Mail expects raw content)
     *
     * @param Booking $booking
     * @return string  Raw PDF binary content
     */
    public function getRawContent(Booking $booking): string
    {
        $pdf = $this->generate($booking);

        // output() returns the raw PDF binary data
        return $pdf->output();
    }

    /**
     * Generate invoice with custom data overrides
     *
     * Useful for admin corrections or re-generating old invoices
     * with updated hotel information.
     */
    public function generateWithOverrides(Booking $booking, array $overrides = []): \Barryvdh\DomPDF\PDF
    {
        $booking->load(['user', 'hotel', 'room.roomType', 'payment']);

        $priceBreakdown = $this->pricingService->getBreakdown(
            $booking->room->roomType,
            $booking->nights,
            $booking->guests_count
        );

        $qrCode = base64_encode(
            QrCode::format('svg')->size(100)->generate($booking->booking_reference)
        );

        $data = array_merge([
            'booking'        => $booking,
            'hotel'          => $booking->hotel,
            'priceBreakdown' => $priceBreakdown,
            'invoiceDate'    => Carbon::now(),
            'qrCode'         => $qrCode,
        ], $overrides);

        return Pdf::loadView('pdf.invoice', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'dejavusans',
            ]);
    }
}
