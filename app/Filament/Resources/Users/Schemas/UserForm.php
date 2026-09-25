<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(
                        fn (?string $state): ?string => filled($state)
                            ? Hash::make($state)
                            : null
                    )
                    ->minLength(8)
                    ->maxLength(255)
                    ->autocomplete('new-password')
                    ->helperText('Leave blank to keep the current password.'),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20)
                    ->default(null),
                TextInput::make('avatar')
                    ->default(null),
            ]);
    }
}
