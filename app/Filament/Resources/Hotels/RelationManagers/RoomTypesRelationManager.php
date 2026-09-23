<?php

namespace App\Filament\Resources\Hotels\RelationManagers;

use App\Models\Room;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RoomTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'roomTypes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->placeholder(
                    'e.g., Deluxe, Suite, Standard'
                ),

            TextInput::make('price_per_night')
                ->required()
                ->numeric()
                ->prefix('$')
                ->step(0.01),

            TextInput::make('capacity')
                ->required()
                ->numeric()
                ->minValue(1)
                ->maxValue(20)
                ->default(2),

            TextInput::make('total_rooms')
                ->required()
                ->numeric()
                ->minValue(1)
                ->maxValue(100)
                ->default(1)
                ->helperText(
                    'Physical rooms will be auto-created'
                ),

            Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),

            TagsInput::make('amenities')
                ->placeholder('Add amenity')
                ->suggestions([
                    'wifi',
                    'tv',
                    'ac',
                    'minibar',
                    'balcony',
                    'jacuzzi',
                ])
                ->columnSpanFull(),

            FileUpload::make('images')
                ->multiple()
                ->image()
                ->maxFiles(8)
                ->maxSize(5120)
                ->directory('rooms')
                ->disk('public')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')

            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make(
                    'price_per_night'
                )
                    ->money('usd')
                    ->sortable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->formatStateUsing(
                        fn ($state): string =>
                            "👥 {$state} guests"
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_rooms')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make(
                    'available_rooms'
                )
                    ->getStateUsing(
                        fn ($record): int =>
                            $record
                                ->rooms
                                ->where(
                                    'status',
                                    'available'
                                )
                                ->count()
                    )
                    ->badge()
                    ->color('success')
                    ->label('Available'),
            ])

            ->filters([])

            ->headerActions([

                CreateAction::make()
                    ->after(function ($record): void {

                        /*
                         * Auto-generate physical room records
                         * after creating a room type.
                         */

                        $prefix = strtoupper(
                            substr($record->name, 0, 1)
                        );

                        for (
                            $i = 1;
                            $i <= $record->total_rooms;
                            $i++
                        ) {
                            Room::create([
                                'room_type_id' => $record->id,

                                'room_number' =>
                                    $prefix .
                                    str_pad(
                                        $i,
                                        3,
                                        '0',
                                        STR_PAD_LEFT
                                    ),

                                'floor' =>
                                    (int) ceil($i / 4),

                                'status' => 'available',

                                'is_available' => true,
                            ]);
                        }
                    }),
            ])

            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])

            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}