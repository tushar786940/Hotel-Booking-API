<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class RevenueChart extends ChartWidget
{
    public function getHeading(): ?string
    {
        return 'Revenue Overview';
    }

    // Chart height
    protected static ?int $sort = 2;

    /**
     * Filter options above the chart
     * Users can switch between time periods
     */
    protected function getFilters(): ?array
    {
        return [
            'week'   => 'This Week',
            'month'  => 'This Month',
            'year'   => 'This Year',
        ];
    }

    /**
     * Define the chart data
     *
     * This returns data for a Line chart showing daily/weekly/monthly revenue.
     */
    protected function getData(): array
    {
        $filter = $this->filter ?? 'month';

        // Determine date range based on filter
        $startDate = match ($filter) {
            'week'  => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year'  => now()->startOfYear(),
        };

        // Group payments by date and sum amounts
        $payments = Payment::where('status', 'completed')
            ->where('paid_at', '>=', $startDate)
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        // Fill in missing dates with 0
        $labels = [];
        $data   = [];
        $current = clone $startDate;

        while ($current <= now()) {
            $dateKey = $current->format('Y-m-d');
            $labels[] = $current->format('M d');
            $data[]   = (float) ($payments[$dateKey] ?? 0);
            $current->addDay();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Revenue ($)',
                    'data'            => $data,
                    'borderColor'     => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.4, // Smooth curves
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Chart type: line, bar, pie, doughnut, etc.
     */
    protected function getType(): string
    {
        return 'line';
    }
}
