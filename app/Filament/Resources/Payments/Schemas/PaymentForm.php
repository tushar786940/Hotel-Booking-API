<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('booking_id')
                    ->required()
                    ->numeric(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('USD'),
                Select::make('method')
                    ->options(['stripe' => 'Stripe', 'paypal' => 'Paypal', 'cash' => 'Cash'])
                    ->default('stripe')
                    ->required(),
                TextInput::make('transaction_id')
                    ->default(null),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ])
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('paid_at'),
                Textarea::make('gateway_response')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
