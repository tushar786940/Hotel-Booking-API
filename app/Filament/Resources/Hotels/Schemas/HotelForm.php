<?php

namespace App\Filament\Resources\Hotels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HotelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('address')
                    ->required(),
                TextInput::make('city')
                    ->required(),
                TextInput::make('state')
                    ->default(null),
                TextInput::make('country')
                    ->required(),
                TextInput::make('zip_code')
                    ->default(null),
                TextInput::make('latitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('longitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('star_rating')
                    ->required()
                    ->numeric()
                    ->default(3),
                TimePicker::make('check_in_time')
                    ->required(),
                TimePicker::make('check_out_time')
                    ->required(),
                Textarea::make('images')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('amenities')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
