<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('booking_reference')
                    ->required(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('room_id')
                    ->required()
                    ->numeric(),
                TextInput::make('hotel_id')
                    ->tel()
                    ->required()
                    ->numeric(),
                DatePicker::make('check_in')
                    ->required(),
                DatePicker::make('check_out')
                    ->required(),
                TextInput::make('guests_count')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('total_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'checked_in' => 'Checked in',
            'checked_out' => 'Checked out',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
        ])
                    ->default('pending')
                    ->required(),
                Textarea::make('special_requests')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('cancelled_at'),
                TextInput::make('cancellation_reason')
                    ->default(null),
            ]);
    }
}
