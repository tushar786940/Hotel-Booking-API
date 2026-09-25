<?php

namespace App\Filament\Resources\Hotels;

use App\Filament\Resources\Hotels\Pages\CreateHotel;
use App\Filament\Resources\Hotels\Pages\EditHotel;
use App\Filament\Resources\Hotels\Pages\ListHotels;
use App\Filament\Resources\Hotels\RelationManagers\BookingsRelationManager;
use App\Filament\Resources\Hotels\RelationManagers\RoomTypesRelationManager;
use App\Models\Hotel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    protected static string|BackedEnum|null $navigationIcon =
    'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup =
    'Hotel Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Hotel Information')
                ->description('Basic hotel details')
                ->icon('heroicon-o-building-office')
                ->schema([

                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(
                            fn(
                                Set $set,
                                ?string $state
                            ) => $set(
                                'slug',
                                Str::slug($state ?? '')
                            )
                        ),

                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabled()
                        ->dehydrated(),

                    Textarea::make('description')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),

                    Select::make('user_id')
                        ->relationship('owner', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->label('Hotel Owner'),

                    Select::make('star_rating')
                        ->options([
                            1 => '★ (1 Star)',
                            2 => '★★ (2 Stars)',
                            3 => '★★★ (3 Stars)',
                            4 => '★★★★ (4 Stars)',
                            5 => '★★★★★ (5 Stars)',
                        ])
                        ->required(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText(
                            'Inactive hotels are hidden from search results'
                        ),
                ])
                ->columns(2),

            Section::make('Location')
                ->description('Address and coordinates')
                ->icon('heroicon-o-map-pin')
                ->schema([

                    TextInput::make('address')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('city')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('state')
                        ->maxLength(100),

                    TextInput::make('country')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('zip_code')
                        ->maxLength(20),

                    TextInput::make('latitude')
                        ->numeric()
                        ->step(0.00000001),

                    TextInput::make('longitude')
                        ->numeric()
                        ->step(0.00000001),
                ])
                ->columns(2),

            Section::make('Hotel Policies')
                ->description('Check-in/out times and amenities')
                ->icon('heroicon-o-clock')
                ->schema([

                    TimePicker::make('check_in_time')
                        ->default('14:00')
                        ->required(),

                    TimePicker::make('check_out_time')
                        ->default('11:00')
                        ->required(),

                    TagsInput::make('amenities')
                        ->placeholder(
                            'Add amenity (e.g., wifi, pool, spa)'
                        )
                        ->suggestions([
                            'wifi',
                            'pool',
                            'spa',
                            'gym',
                            'restaurant',
                            'bar',
                            'parking',
                            'beach_access',
                            'room_service',
                            'laundry',
                            'airport_shuttle',
                            'pet_friendly',
                        ])
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Images')
                ->description('Upload hotel photos')
                ->icon('heroicon-o-photo')
                ->schema([

                    FileUpload::make('images')
                        ->multiple()
                        ->image()
                        ->imageEditor()
                        ->maxFiles(10)
                        ->maxSize(5120)
                        ->directory('hotels')
                        ->disk('public')
                        ->columnSpanFull()
                        ->helperText(
                            'Upload up to 10 images (max 5MB each)'
                        ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\ImageColumn::make('images')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->label('Photos'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn(Hotel $record): string =>
                        $record->city . ', ' . $record->country
                    ),

                Tables\Columns\TextColumn::make('owner.name')
                    ->searchable()
                    ->sortable()
                    ->label('Owner')
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('star_rating')
                    ->formatStateUsing(
                        fn($state): string =>
                        str_repeat('★', (int) $state) .
                            str_repeat(
                                '☆',
                                max(0, 5 - (int) $state)
                            )
                    )
                    ->sortable()
                    ->label('Rating'),

                Tables\Columns\TextColumn::make(
                    'roomTypes.price_per_night'
                )
                    ->money('usd')
                    ->sortable()
                    ->label('From / Night')
                    ->getStateUsing(
                        fn(Hotel $record) =>
                        $record
                            ->roomTypes()
                            ->min('price_per_night')
                    )
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->counts('bookings')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->label('Bookings')
                    ->visibleFrom('lg'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    )
                    ->visibleFrom('xl'),
            ])

            ->filters([

                Tables\Filters\SelectFilter::make('star_rating')
                    ->options([
                        1 => '1 Star',
                        2 => '2 Stars',
                        3 => '3 Stars',
                        4 => '4 Stars',
                        5 => '5 Stars',
                    ]),

                Tables\Filters\SelectFilter::make('city')
                    ->options(
                        fn() =>
                        Hotel::query()
                            ->whereNotNull('city')
                            ->distinct()
                            ->pluck('city', 'city')
                            ->toArray()
                    )
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])

            ->actions([

                ViewAction::make(),

                EditAction::make(),

                Action::make('toggle_active')
                    ->label(
                        fn(Hotel $record): string =>
                        $record->is_active
                            ? 'Deactivate'
                            : 'Activate'
                    )
                    ->icon(
                        fn(Hotel $record): string =>
                        $record->is_active
                            ? 'heroicon-o-x-circle'
                            : 'heroicon-o-check-circle'
                    )
                    ->color(
                        fn(Hotel $record): string =>
                        $record->is_active
                            ? 'danger'
                            : 'success'
                    )
                    ->requiresConfirmation()
                    ->action(
                        fn(Hotel $record): bool =>
                        $record->update([
                            'is_active' => ! $record->is_active,
                        ])
                    ),
            ])

            ->bulkActions([

                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('created_at', 'desc')
            ->stackedOnMobile();
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::active()->count();
    }

    public static function getRelations(): array
    {
        return [
            RoomTypesRelationManager::class,
            BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHotels::route('/'),
            'create' => CreateHotel::route('/create'),
            'edit' => EditHotel::route('/{record}/edit'),
        ];
    }
}
