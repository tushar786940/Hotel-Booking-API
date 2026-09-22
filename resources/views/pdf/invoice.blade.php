<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Invoice - {{ $booking->booking_reference }}</title>

    {{--
        All CSS must be inside <style> tags.
        DomPDF supports most CSS2 and some CSS3.
        Flexbox and Grid are NOT supported — use tables and floats!
    --}}
    <style>
        /* ─── Reset & Base ─── */
        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333333;
            line-height: 1.5;
            padding: 20px;
        }

        /* ─── Header ─── */
        .header {
            width: 100%;
            margin-bottom: 25px;
            border-bottom: 3px solid #1a56db;
            padding-bottom: 15px;
        }

        .header-left {
            float: left;
            width: 55%;
        }

        .header-right {
            float: right;
            width: 40%;
            text-align: right;
        }

        .hotel-name {
            font-size: 22px;
            font-weight: bold;
            color: #1a56db;
            margin-bottom: 4px;
        }

        .hotel-details {
            font-size: 9px;
            color: #666666;
            line-height: 1.6;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #1a56db;
            margin-bottom: 5px;
        }

        .invoice-number {
            font-size: 11px;
            color: #666666;
        }

        .invoice-date {
            font-size: 10px;
            color: #888888;
            margin-top: 3px;
        }

        /* ─── Clearfix (DomPDF needs this for floats) ─── */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* ─── Info Section ─── */
        .info-section {
            width: 100%;
            margin-bottom: 25px;
        }

        .info-box {
            float: left;
            width: 48%;
            background-color: #f8f9fa;
            padding: 12px 15px;
            border-radius: 4px;
            border-left: 3px solid #1a56db;
        }

        .info-box-right {
            float: right;
        }

        .info-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #888888;
            letter-spacing: 1px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .info-value {
            font-size: 11px;
            color: #333333;
            line-height: 1.6;
        }

        .info-value strong {
            color: #1a56db;
        }

        /* ─── Booking Details ─── */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1a56db;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* ─── Table ─── */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th {
            background-color: #1a56db;
            color: #ffffff;
            padding: 10px 12px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table th:last-child {
            text-align: right;
        }

        .table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }

        .table td:last-child {
            text-align: right;
            font-weight: bold;
        }

        .table .description {
            color: #666666;
            font-size: 9px;
        }

        /* ─── Totals ─── */
        .totals-section {
            float: right;
            width: 300px;
            margin-bottom: 25px;
        }

        .totals-row {
            padding: 6px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .totals-row .label {
            float: left;
            color: #666666;
        }

        .totals-row .value {
            float: right;
            font-weight: bold;
        }

        .totals-row.grand-total {
            border-top: 2px solid #1a56db;
            border-bottom: none;
            padding-top: 10px;
            margin-top: 5px;
        }

        .totals-row.grand-total .label {
            font-size: 13px;
            font-weight: bold;
            color: #333333;
        }

        .totals-row.grand-total .value {
            font-size: 16px;
            color: #1a56db;
        }

        /* ─── Payment Status Badge ─── */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-refunded {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* ─── QR Code Section ─── */
        .qr-section {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .qr-section img {
            width: 100px;
            height: 100px;
        }

        .qr-label {
            font-size: 8px;
            color: #888888;
            margin-top: 5px;
        }

        /* ─── Footer ─── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #999999;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        .footer-line {
            margin-bottom: 3px;
        }

        /* ─── Special Requests ─── */
        .special-requests {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 10px 15px;
            margin-bottom: 20px;
            font-size: 10px;
        }

        .special-requests strong {
            color: #92400e;
        }

        /* ─── Notes ─── */
        .notes {
            font-size: 9px;
            color: #888888;
            line-height: 1.6;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #d1d5db;
        }
    </style>
</head>

<body>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- HEADER: Hotel Info + Invoice Title          --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="header clearfix">
        <div class="header-left">
            <div class="hotel-name">{{ $hotel->name }}</div>
            <div class="hotel-details">
                {{ $hotel->address }}<br>
                {{ $hotel->city }}, {{ $hotel->state }} {{ $hotel->zip_code }}<br>
                {{ $hotel->country }}<br>
                @if ($hotel->phone ?? false)
                    Tel: {{ $hotel->phone }}<br>
                @endif
                {{ str_repeat('★', $hotel->star_rating) }}{{ str_repeat('☆', 5 - $hotel->star_rating) }}
                {{ $hotel->star_rating }}-Star Hotel
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-number">
                <strong>{{ $booking->booking_reference }}</strong>
            </div>
            <div class="invoice-date">
                Date: {{ $invoiceDate->format('F d, Y') }}
            </div>
            <div style="margin-top: 8px;">
                @if ($booking->status === 'confirmed' || $booking->status === 'checked_out')
                    <span class="status-badge status-paid">✓ PAID</span>
                @elseif($booking->status === 'cancelled' || $booking->status === 'refunded')
                    <span class="status-badge status-refunded">✗ {{ strtoupper($booking->status) }}</span>
                @else
                    <span class="status-badge status-pending">⏳ PENDING</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- BILL TO + BOOKING INFO                      --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="info-section clearfix">
        <div class="info-box">
            <div class="info-label">Bill To</div>
            <div class="info-value">
                <strong>{{ $booking->user->name }}</strong><br>
                {{ $booking->user->email }}<br>
                @if ($booking->user->phone)
                    {{ $booking->user->phone }}<br>
                @endif
            </div>
        </div>
        <div class="info-box info-box-right">
            <div class="info-label">Booking Details</div>
            <div class="info-value">
                <strong>Check-in:</strong> {{ $booking->check_in->format('D, M d, Y') }}<br>
                <strong>Check-out:</strong> {{ $booking->check_out->format('D, M d, Y') }}<br>
                <strong>Duration:</strong> {{ $booking->nights }} Night(s)<br>
                <strong>Guests:</strong> {{ $booking->guests_count }}
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- ROOM DETAILS TABLE                          --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="section-title">Room Details</div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 10%;">Room</th>
                <th style="width: 30%;">Type</th>
                <th style="width: 25%;">Dates</th>
                <th style="width: 15%;">Nights</th>
                <th style="width: 20%; text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>#{{ $booking->room->room_number }}</strong></td>
                <td>
                    {{ $booking->room->roomType->name }}
                    <br>
                    <span class="description">
                        Floor {{ $booking->room->floor }} ·
                        Up to {{ $booking->room->roomType->capacity }} guests
                    </span>
                </td>
                <td>
                    {{ $booking->check_in->format('M d') }} –
                    {{ $booking->check_out->format('M d, Y') }}
                </td>
                <td>{{ $booking->nights }}</td>
                <td>${{ number_format($priceBreakdown['base_price'], 2) }}</td>
            </tr>

            {{-- Extra guest charges (if any) --}}
            @if ($priceBreakdown['extra_guest_charge'] > 0)
                <tr>
                    <td></td>
                    <td>
                        Extra Guest Surcharge
                        <br>
                        <span class="description">
                            {{ $priceBreakdown['extra_guests'] }} extra guest(s)
                            × ${{ number_format($booking->room->roomType->price_per_night * 0.2, 2) }}/night
                        </span>
                    </td>
                    <td></td>
                    <td>{{ $booking->nights }}</td>
                    <td>${{ number_format($priceBreakdown['extra_guest_charge'], 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- SPECIAL REQUESTS (if any)                   --}}
    {{-- ═══════════════════════════════════════════ --}}
    @if ($booking->special_requests)
        <div class="special-requests">
            <strong>📝 Special Requests:</strong>
            {{ $booking->special_requests }}
        </div>
    @endif

    {{-- ═══════════════════════════════════════════ --}}
    {{-- PRICE TOTALS                                --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="totals-section">
        <div class="totals-row clearfix">
            <span class="label">Room Rate ({{ $booking->nights }} nights)</span>
            <span class="value">${{ number_format($priceBreakdown['base_price'], 2) }}</span>
        </div>

        @if ($priceBreakdown['extra_guest_charge'] > 0)
            <div class="totals-row clearfix">
                <span class="label">Extra Guest Charge</span>
                <span class="value">${{ number_format($priceBreakdown['extra_guest_charge'], 2) }}</span>
            </div>
        @endif

        <div class="totals-row clearfix">
            <span class="label">Subtotal</span>
            <span class="value">${{ number_format($priceBreakdown['subtotal'], 2) }}</span>
        </div>

        <div class="totals-row clearfix">
            <span class="label">Tax ({{ $priceBreakdown['tax_rate'] }})</span>
            <span class="value">${{ number_format($priceBreakdown['tax'], 2) }}</span>
        </div>

        <div class="totals-row grand-total clearfix">
            <span class="label">Total Due</span>
            <span class="value">${{ number_format($priceBreakdown['total'], 2) }}</span>
        </div>
    </div>

    <div class="clearfix"></div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- PAYMENT DETAILS                             --}}
    {{-- ═══════════════════════════════════════════ --}}
    @if ($booking->payment)
        <div class="section-title" style="margin-top: 20px;">Payment Information</div>
        <table class="table">
            <tbody>
                <tr>
                    <td style="width: 30%; color: #888;">Payment Method</td>
                    <td><strong>{{ ucfirst($booking->payment->method) }}</strong></td>
                </tr>
                <tr>
                    <td style="color: #888;">Transaction ID</td>
                    <td><code>{{ $booking->payment->transaction_id ?? 'N/A' }}</code></td>
                </tr>
                <tr>
                    <td style="color: #888;">Payment Date</td>
                    <td>
                        {{ $booking->payment->paid_at ? $booking->payment->paid_at->format('F d, Y \a\t h:i A') : 'Pending' }}
                    </td>
                </tr>
                <tr>
                    <td style="color: #888;">Status</td>
                    <td>
                        @if ($booking->payment->status === 'completed')
                            <span class="status-badge status-paid">✓ Completed</span>
                        @elseif($booking->payment->status === 'refunded')
                            <span class="status-badge status-refunded">↩ Refunded</span>
                        @else
                            <span class="status-badge status-pending">⏳ {{ ucfirst($booking->payment->status) }}</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- ═══════════════════════════════════════════ --}}
    {{-- QR CODE (for verification)                  --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="qr-section">
        <img src="data:image/svg+xml;base64,{{ $qrCode }}" width="100" height="100" alt="Booking QR Code">
        <div class="qr-label">
            Scan to verify booking · {{ $booking->booking_reference }}
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- NOTES & TERMS                               --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="notes">
        <strong>Terms & Conditions:</strong><br>
        1. Check-in time: {{ $hotel->check_in_time }} | Check-out time: {{ $hotel->check_out_time }}<br>
        2. Cancellations made less than 24 hours before check-in may incur a fee.<br>
        3. This invoice serves as proof of payment. Please retain for your records.<br>
        4. For any queries, contact us with your booking reference: <strong>{{ $booking->booking_reference }}</strong>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- FOOTER                                      --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="footer">
        <div class="footer-line">
            <strong>{{ $hotel->name }}</strong> ·
            {{ $hotel->address }}, {{ $hotel->city }}, {{ $hotel->country }}
        </div>
        <div class="footer-line">
            This is a computer-generated invoice. No signature required.
        </div>
        <div class="footer-line">
            Generated on {{ $invoiceDate->format('F d, Y \a\t h:i A') }}
        </div>
    </div>

</body>

</html>
