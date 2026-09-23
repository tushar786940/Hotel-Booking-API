<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'User Management';
    }

    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Personal Information')
                ->description('Basic user details')
                ->icon('heroicon-o-user')
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255)
                        ->autocomplete('name'),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->autocomplete('email'),

                    TextInput::make('phone')
                        ->label('Phone')
                        ->tel()
                        ->maxLength(20)
                        ->autocomplete('tel'),

                    TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->required(
                            fn (string $operation): bool =>
                                $operation === 'create'
                        )
                        ->dehydrated(
                            fn (?string $state): bool =>
                                filled($state)
                        )
                        ->dehydrateStateUsing(
                            fn (?string $state): ?string =>
                                filled($state)
                                    ? Hash::make($state)
                                    : null
                        )
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make('Role & Status')
                ->description('Assign roles and permissions')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Select::make('roles')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->label('Roles'),
                ]),
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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(
                        fn (string $state): string => match ($state) {
                            'admin' => 'danger',
                            'hotel-owner' => 'info',
                            'guest' => 'gray',
                            default => 'secondary',
                        }
                    ),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->counts('bookings')
                    ->label('Bookings')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->relationship('roles', 'name')
                    ->label('Filter by Role')
                    ->multiple(),

                Tables\Filters\Filter::make('has_bookings')
                    ->label('Has Bookings')
                    ->query(
                        fn (Builder $query): Builder =>
                            $query->has('bookings')
                    ),

                Tables\Filters\Filter::make('created_today')
                    ->label('Created Today')
                    ->query(
                        fn (Builder $query): Builder =>
                            $query->whereDate(
                                'created_at',
                                today()
                            )
                    ),
            ])

            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])

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
        return (string) static::getModel()::count();
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public static function getRelations(): array
    {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}