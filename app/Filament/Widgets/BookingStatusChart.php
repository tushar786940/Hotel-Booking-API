<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class BookingStatusChart extends ChartWidget
{
    // $sort is always static in Filament widgets
    protected static ?int $sort = 3;

    // Use this method instead of declaring a $heading property
    public function getHeading(): ?string
    {
        return 'Bookings by Status';
    }

    protected function getData(): array
    {
        $statuses = Booking::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data'  => [
                        $statuses['pending'] ?? 0,
                        $statuses['confirmed'] ?? 0,
                        $statuses['checked_in'] ?? 0,
                        $statuses['checked_out'] ?? 0,
                        $statuses['cancelled'] ?? 0,
                        $statuses['refunded'] ?? 0,
                    ],
                    'backgroundColor' => [
                        '#f59e0b', // pending - amber
                        '#10b981', // confirmed - green
                        '#3b82f6', // checked_in - blue
                        '#6b7280', // checked_out - gray
                        '#ef4444', // cancelled - red
                        '#8b5cf6', // refunded - purple
                    ],
                ],
            ],
            'labels' => [
                'Pending', 'Confirmed', 'Checked In',
                'Checked Out', 'Cancelled', 'Refunded',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}