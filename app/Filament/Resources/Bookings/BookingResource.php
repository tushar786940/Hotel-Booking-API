<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup =
        'Hotel Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute =
        'booking_reference';

    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Booking Details')
                ->schema([

                    TextInput::make('booking_reference')
                        ->disabled()
                        ->dehydrated(),

                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->label('Guest'),

                    Select::make('hotel_id')
                        ->relationship('hotel', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->label('Hotel'),

                    Select::make('room_id')
                        ->relationship('room', 'room_number')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->label('Room'),

                    DatePicker::make('check_in')
                        ->required()
                        ->minDate(now()->addDay()),

                    DatePicker::make('check_out')
                        ->required()
                        ->after('check_in'),

                    TextInput::make('guests_count')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(1),

                    TextInput::make('total_price')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->disabled()
                        ->dehydrated(),

                    Select::make('status')
                        ->options([
                            'pending' => '⏳ Pending',
                            'confirmed' => '✅ Confirmed',
                            'checked_in' => '🔑 Checked In',
                            'checked_out' => '👋 Checked Out',
                            'cancelled' => '❌ Cancelled',
                            'refunded' => '↩️ Refunded',
                        ])
                        ->required(),

                    Textarea::make('special_requests')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make(
                    'booking_reference'
                )
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Reference copied!'),

                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Guest')
                    ->description(
                        fn (Booking $record): string =>
                            $record->user?->email ?? ''
                    ),

                Tables\Columns\TextColumn::make('hotel.name')
                    ->searchable()
                    ->sortable()
                    ->label('Hotel'),

                Tables\Columns\TextColumn::make(
                    'room.room_number'
                )
                    ->sortable()
                    ->label('Room')
                    ->description(
                        fn (Booking $record): string =>
                            $record->room?->roomType?->name ?? ''
                    ),

                Tables\Columns\TextColumn::make('check_in')
                    ->date('M d, Y')
                    ->sortable()
                    ->description(
                        fn (Booking $record): string =>
                            $record->check_in
                                ? $record->check_in->diffForHumans()
                                : ''
                    ),

                Tables\Columns\TextColumn::make('check_out')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nights')
                    ->getStateUsing(
                        fn (Booking $record): int =>
                            (int) $record->nights
                    )
                    ->suffix(' night(s)')
                    ->sortable(),

                Tables\Columns\TextColumn::make('guests_count')
                    ->formatStateUsing(
                        fn ($state): string =>
                            "👥 {$state}"
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->money('usd')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(
                        fn (string $state): string => match ($state) {
                            'pending' => 'warning',
                            'confirmed' => 'success',
                            'checked_in' => 'info',
                            'checked_out' => 'gray',
                            'cancelled' => 'danger',
                            'refunded' => 'danger',
                            default => 'secondary',
                        }
                    )
                    ->sortable(),
            ])

            /*
            |--------------------------------------------------------------------------
            | Filters
            |--------------------------------------------------------------------------
            */

            ->filters([

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'checked_in' => 'Checked In',
                        'checked_out' => 'Checked Out',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('hotel')
                    ->relationship('hotel', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('check_in')
                    ->form([

                        DatePicker::make('check_in_from')
                            ->label('From'),

                        DatePicker::make('check_in_until')
                            ->label('Until'),
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {

                            return $query
                                ->when(
                                    $data['check_in_from'] ?? null,
                                    fn (
                                        Builder $query,
                                        $date
                                    ) =>
                                        $query->whereDate(
                                            'check_in',
                                            '>=',
                                            $date
                                        )
                                )
                                ->when(
                                    $data['check_in_until'] ?? null,
                                    fn (
                                        Builder $query,
                                        $date
                                    ) =>
                                        $query->whereDate(
                                            'check_in',
                                            '<=',
                                            $date
                                        )
                                );
                        }
                    ),
            ])

            /*
            |--------------------------------------------------------------------------
            | Actions
            |--------------------------------------------------------------------------
            */

            ->actions([

                ViewAction::make(),

                EditAction::make(),

                Action::make('check_in')
                    ->label('Check In')
                    ->icon('heroicon-o-key')
                    ->color('info')
                    ->visible(
                        fn (Booking $record): bool =>
                            $record->status === 'confirmed'
                    )
                    ->action(
                        function (Booking $record): void {

                            $record->update([
                                'status' => 'checked_in',
                            ]);

                            $record->room?->update([
                                'status' => 'occupied',
                            ]);
                        }
                    )
                    ->requiresConfirmation()
                    ->successNotificationTitle(
                        'Guest checked in!'
                    ),

                Action::make('check_out')
                    ->label('Check Out')
                    ->icon(
                        'heroicon-o-arrow-right-on-rectangle'
                    )
                    ->color('warning')
                    ->visible(
                        fn (Booking $record): bool =>
                            $record->status === 'checked_in'
                    )
                    ->action(
                        function (Booking $record): void {

                            $record->update([
                                'status' => 'checked_out',
                            ]);

                            $record->room?->update([
                                'status' => 'available',
                            ]);
                        }
                    )
                    ->requiresConfirmation()
                    ->successNotificationTitle(
                        'Guest checked out!'
                    ),
            ])

            /*
            |--------------------------------------------------------------------------
            | Bulk Actions
            |--------------------------------------------------------------------------
            */

            ->bulkActions([

                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('created_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Navigation Badge
    |--------------------------------------------------------------------------
    */

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()
            ::whereIn(
                'status',
                [
                    'pending',
                    'confirmed',
                    'checked_in',
                ]
            )
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            // 'view' => ViewBooking::route('/{record}'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }
}