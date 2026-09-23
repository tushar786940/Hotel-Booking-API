<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\Room;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class Analytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.analytics';

    /**
     * Pass data to the Blade view.
     */
    protected function getViewData(): array
    {
        return [
            'totalRevenue' => Payment::where('status', 'completed')
                ->sum('amount'),

            'monthlyRevenue' => Payment::where('status', 'completed')
                ->whereMonth('paid_at', now()->month)
                ->sum('amount'),

            'totalBookings' => Booking::count(),

            'activeBookings' => Booking::whereIn(
                'status',
                ['confirmed', 'checked_in']
            )->count(),

            'occupancyRate' => $this->calculateOccupancyRate(),

            'avgBookingValue' => Booking::avg('total_price') ?? 0,

            'topHotels' => Hotel::withCount('bookings')
                ->orderByDesc('bookings_count')
                ->take(5)
                ->get(),
        ];
    }

    protected function calculateOccupancyRate(): float
    {
        $totalRooms = Room::where('is_available', true)->count();

        $occupiedRooms = Room::where('status', 'occupied')->count();

        if ($totalRooms === 0) {
            return 0.0;
        }

        return round(
            ($occupiedRooms / $totalRooms) * 100,
            1
        );
    }
}