<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup =
        'Hotel Management';

    protected static ?int $navigationSort = 5;

    /**
     * Reviews are read-only.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Reviewer'),

                Tables\Columns\TextColumn::make('hotel.name')
                    ->searchable()
                    ->sortable()
                    ->label('Hotel'),

                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(
                        fn (?int $state): string =>
                            str_repeat('⭐', max(0, min(5, $state ?? 0))) .
                            str_repeat('☆', max(0, 5 - ($state ?? 0)))
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('comment')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(
                        fn (Review $record): ?string =>
                            $record->comment !== null &&
                            strlen($record->comment) > 50
                                ? $record->comment
                                : null
                    ),

                Tables\Columns\TextColumn::make('booking.booking_reference')
                    ->searchable()
                    ->label('Booking'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->label('Reviewed'),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        5 => '⭐⭐⭐⭐⭐ (5)',
                        4 => '⭐⭐⭐⭐ (4)',
                        3 => '⭐⭐⭐ (3)',
                        2 => '⭐⭐ (2)',
                        1 => '⭐ (1)',
                    ]),

                Tables\Filters\SelectFilter::make('hotel')
                    ->relationship('hotel', 'name')
                    ->searchable()
                    ->preload(),
            ])

            ->actions([
                ViewAction::make(),

                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Review')
                    ->modalDescription(
                        'Are you sure? This cannot be undone.'
                    ),
            ])

            ->defaultSort('created_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
        ];
    }
}
