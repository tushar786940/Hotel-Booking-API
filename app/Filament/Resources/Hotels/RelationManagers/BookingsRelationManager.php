<?php

namespace App\Filament\Resources\Hotels\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('booking_reference')
            ->columns([
                Tables\Columns\TextColumn::make('booking_reference')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->label('Guest'),

                Tables\Columns\TextColumn::make('room.room_number')
                    ->label('Room'),

                Tables\Columns\TextColumn::make('check_in')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_out')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->money('usd')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'     => 'warning',
                        'confirmed'   => 'success',
                        'checked_in'  => 'info',
                        'checked_out' => 'gray',
                        'cancelled'   => 'danger',
                        'refunded'    => 'danger',
                        default       => 'secondary',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'     => 'Pending',
                        'confirmed'   => 'Confirmed',
                        'checked_in'  => 'Checked In',
                        'checked_out' => 'Checked Out',
                        'cancelled'   => 'Cancelled',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }
}