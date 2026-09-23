<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    /**
     * Stats cards displayed on the dashboard
     *
     * Each Stat card shows:
     * - A label (title)
     * - A value (number)
     * - A description (trend/change)
     * - A color
     * - An icon
     * - An optional chart sparkline
     */
    protected function getStats(): array
    {
        // ─── Calculate metrics ───

        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $lastMonthRevenue = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [now()->subMonth(), now()])
            ->sum('amount');

        $activeBookings = Booking::whereIn('status', ['pending', 'confirmed', 'checked_in'])->count();
        $totalBookings = Booking::count();

        $totalGuests = User::role('guest')->count();
        $newGuestsThisMonth = User::role('guest')
            ->whereMonth('created_at', now()->month)
            ->count();

        $totalHotels = Hotel::active()->count();

        return [
            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('$' . number_format($lastMonthRevenue, 2) . ' this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 8, 9, 7, 10, 12]) // Sparkline
                ->extraAttributes(['class' => 'cursor-pointer']),

            Stat::make('Active Bookings', $activeBookings)
                ->description($totalBookings . ' total bookings')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart([2, 5, 3, 7, 4, 8, 6, 9, 5, 10, 8, 12]),

            Stat::make('Total Guests', $totalGuests)
                ->description($newGuestsThisMonth . ' new this month')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary')
                ->chart([1, 2, 3, 2, 4, 3, 5, 4, 6, 5, 7, 8]),

            Stat::make('Active Hotels', $totalHotels)
                ->description('Currently listed')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('warning'),
        ];
    }
}
