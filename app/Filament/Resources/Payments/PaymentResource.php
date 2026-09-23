<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Payment;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static string|UnitEnum|null $navigationGroup = 'Hotel Management';
    protected static ?int $navigationSort = 4;

    /**
     * Payments are read-only.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking.booking_reference')
                    ->searchable()
                    ->sortable()
                    ->label('Booking')
                    ->url(
                        fn (Payment $record): string => BookingResource::getUrl(
                            'view',
                            ['record' => $record->booking_id]
                        )
                    ),

                Tables\Columns\TextColumn::make('booking.user.name')
                    ->searchable()
                    ->label('Guest'),

                Tables\Columns\TextColumn::make('amount')
                    ->money('usd')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('currency')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(
                        fn (?string $state): string => strtoupper($state ?? '')
                    ),

                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->icon(
                        fn (?string $state): string => match ($state) {
                            'stripe' => 'heroicon-o-credit-card',
                            'paypal' => 'heroicon-o-globe-alt',
                            'cash' => 'heroicon-o-banknotes',
                            default => 'heroicon-o-question-mark-circle',
                        }
                    )
                    ->formatStateUsing(
                        fn (?string $state): string => ucfirst($state ?? '')
                    ),

                Tables\Columns\TextColumn::make('transaction_id')
                    ->searchable()
                    ->copyable()
                    ->limit(20)
                    ->tooltip(
                        fn (Payment $record): ?string => $record->transaction_id
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'completed' => 'success',
                            'pending' => 'warning',
                            'failed' => 'danger',
                            'refunded' => 'info',
                            default => 'gray',
                        }
                    )
                    ->formatStateUsing(
                        fn (?string $state): string => ucfirst($state ?? '')
                    ),

                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->placeholder('Not paid yet'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),

                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'cash' => 'Cash',
                    ]),
            ])

            ->actions([
                ViewAction::make(),
            ])

            ->defaultSort('created_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        $total = static::getModel()::where('status', 'completed')
            ->sum('amount');

        return '$' . number_format((float) $total, 0);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
        ];
    }
}