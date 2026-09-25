<x-filament-panels::page>
    <style>
        .hotel-analytics-stats {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .hotel-analytics-stat {
            min-width: 0;
            text-align: center;
        }

        .hotel-analytics-stat-label,
        .hotel-analytics-muted {
            color: #6b7280;
        }

        .hotel-analytics-stat-label,
        .hotel-analytics-bookings-label,
        .hotel-analytics-empty {
            font-size: 0.875rem;
        }

        .hotel-analytics-stat-value {
            overflow-wrap: anywhere;
            margin-top: 0.25rem;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 2rem;
        }

        .hotel-analytics-stat-value-primary {
            color: #2563eb;
        }

        .hotel-analytics-stat-value-success {
            color: #059669;
        }

        .hotel-analytics-stat-value-info {
            color: #0284c7;
        }

        .hotel-analytics-stat-value-warning {
            color: #d97706;
        }

        .hotel-analytics-mobile-list {
            display: grid;
            gap: 0.75rem;
        }

        .hotel-analytics-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .hotel-analytics-card-content {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .hotel-analytics-card-details {
            min-width: 0;
        }

        .hotel-analytics-position {
            color: #2563eb;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .hotel-analytics-name,
        .hotel-analytics-location {
            overflow-wrap: anywhere;
        }

        .hotel-analytics-name {
            color: #111827;
            font-weight: 600;
        }

        .hotel-analytics-location {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .hotel-analytics-bookings {
            flex-shrink: 0;
            text-align: right;
        }

        .hotel-analytics-bookings-label {
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .hotel-analytics-table-wrap {
            display: none;
            overflow-x: auto;
        }

        .hotel-analytics-table {
            width: 100%;
            min-width: 40rem;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .hotel-analytics-table th,
        .hotel-analytics-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 0.75rem 1rem 0.75rem 0;
            text-align: left;
        }

        .hotel-analytics-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .hotel-analytics-table th:last-child,
        .hotel-analytics-table td:last-child {
            padding-right: 0;
            text-align: right;
            white-space: nowrap;
        }

        .hotel-analytics-table-position {
            color: #2563eb;
            font-weight: 700;
            white-space: nowrap;
        }

        .hotel-analytics-table-name {
            font-weight: 600;
        }

        .hotel-analytics-empty {
            color: #6b7280;
            padding: 1.5rem 0;
            text-align: center;
        }

        html.dark .hotel-analytics-stat-label,
        html.dark .hotel-analytics-muted,
        html.dark .hotel-analytics-location,
        html.dark .hotel-analytics-bookings-label,
        html.dark .hotel-analytics-empty {
            color: #9ca3af;
        }

        html.dark .hotel-analytics-card,
        html.dark .hotel-analytics-table th,
        html.dark .hotel-analytics-table td {
            border-color: rgba(255, 255, 255, 0.1);
        }

        html.dark .hotel-analytics-name {
            color: #fff;
        }

        @media (min-width: 640px) {
            .hotel-analytics-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1.5rem;
            }

            .hotel-analytics-stat-value {
                font-size: 1.875rem;
                line-height: 2.25rem;
            }
        }

        @media (min-width: 768px) {
            .hotel-analytics-mobile-list {
                display: none;
            }

            .hotel-analytics-table-wrap {
                display: block;
            }
        }

        @media (min-width: 1024px) {
            .hotel-analytics-stats {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
    </style>

    <div class="hotel-analytics-stats">
        <x-filament::section>
            <div class="hotel-analytics-stat">
                <p class="hotel-analytics-stat-label">Total Revenue</p>
                <p class="hotel-analytics-stat-value hotel-analytics-stat-value-primary">
                    ${{ number_format($totalRevenue, 2) }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="hotel-analytics-stat">
                <p class="hotel-analytics-stat-label">Monthly Revenue</p>
                <p class="hotel-analytics-stat-value hotel-analytics-stat-value-success">
                    ${{ number_format($monthlyRevenue, 2) }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="hotel-analytics-stat">
                <p class="hotel-analytics-stat-label">Occupancy Rate</p>
                <p class="hotel-analytics-stat-value hotel-analytics-stat-value-info">
                    {{ $occupancyRate }}%
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="hotel-analytics-stat">
                <p class="hotel-analytics-stat-label">Avg Booking Value</p>
                <p class="hotel-analytics-stat-value hotel-analytics-stat-value-warning">
                    ${{ number_format($avgBookingValue, 2) }}
                </p>
            </div>
        </x-filament::section>
    </div>

    <x-filament::section heading="🏆 Top Performing Hotels">
        <div class="hotel-analytics-mobile-list">
            @forelse ($topHotels as $index => $hotel)
                <article class="hotel-analytics-card">
                    <div class="hotel-analytics-card-content">
                        <div class="hotel-analytics-card-details">
                            <p class="hotel-analytics-position">No. {{ $index + 1 }}</p>
                            <p class="hotel-analytics-name">{{ $hotel->name }}</p>
                            <p class="hotel-analytics-location">
                                {{ $hotel->city }}, {{ $hotel->country }}
                            </p>
                        </div>

                        <div class="hotel-analytics-bookings">
                            <p class="hotel-analytics-bookings-label">Bookings</p>
                            <x-filament::badge color="info">
                                {{ $hotel->bookings_count }}
                            </x-filament::badge>
                        </div>
                    </div>
                </article>
            @empty
                <p class="hotel-analytics-empty">No hotel performance data is available yet.</p>
            @endforelse
        </div>

        <div class="hotel-analytics-table-wrap">
            <table class="hotel-analytics-table">
                <thead>
                    <tr>
                        <th scope="col">No.</th>
                        <th scope="col">Hotel</th>
                        <th scope="col">City</th>
                        <th scope="col">Total Bookings</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topHotels as $index => $hotel)
                        <tr>
                            <td class="hotel-analytics-table-position">{{ $index + 1 }}</td>
                            <td class="hotel-analytics-table-name">{{ $hotel->name }}</td>
                            <td class="hotel-analytics-muted">{{ $hotel->city }}, {{ $hotel->country }}</td>
                            <td>
                                <x-filament::badge color="info">
                                    {{ $hotel->bookings_count }}
                                </x-filament::badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="hotel-analytics-empty">
                                No hotel performance data is available yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
