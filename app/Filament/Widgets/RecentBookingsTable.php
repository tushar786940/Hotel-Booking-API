<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentBookingsTable extends BaseWidget
{
    protected static ?int $sort = 4;

    // Show only the latest 5
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->with(['user', 'hotel', 'room.roomType'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('booking_reference')
                    ->weight('bold')
                    ->url(fn (Booking $record): string =>
                        BookingResource::getUrl('view', ['record' => $record->id])
                    ),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Guest'),

                Tables\Columns\TextColumn::make('hotel.name')
                    ->label('Hotel'),

                Tables\Columns\TextColumn::make('check_in')
                    ->date('M d')
                    ->label('Check-in'),

                Tables\Columns\TextColumn::make('total_price')
                    ->money('usd'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'     => 'warning',
                        'confirmed'   => 'success',
                        'checked_in'  => 'info',
                        'checked_out' => 'gray',
                        'cancelled'   => 'danger',
                        default       => 'secondary',
                    }),
            ])
            ->paginated(false); // No pagination for widget
    }
}